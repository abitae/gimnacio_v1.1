<?php

namespace App\Services;

use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\EnrollmentInstallment;
use Illuminate\Support\Collection;

class DailyOperationsDebtService
{
    public function __construct(
        protected ClienteMatriculaService $clienteMatriculaService,
        protected ClienteMembresiaService $clienteMembresiaService,
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function actionableItemsForCliente(int $clienteId): Collection
    {
        $items = collect();

        $matriculas = $this->clienteMatriculaService->getByCliente($clienteId, [], 100);
        foreach ($matriculas->items() as $matricula) {
            if ($matricula->usaPlanCuotas()) {
                continue;
            }

            $saldo = $this->clienteMatriculaService->obtenerSaldoPendiente($matricula->id);
            if ($saldo <= 0) {
                continue;
            }

            $items->push([
                'tipo' => 'matricula',
                'id' => $matricula->id,
                'nombre' => $matricula->nombre,
                'saldo_pendiente' => round($saldo, 2),
                'estado' => $matricula->estado,
                'es_vencida' => $this->contratoEstaVencido($matricula->fecha_fin, $matricula->estado),
                'fecha_vencimiento' => $matricula->fecha_fin,
                'detalle' => $matricula->esClase() ? 'Clase' : 'Matrícula',
            ]);
        }

        $membresias = $this->clienteMembresiaService->getByCliente($clienteId, null, 100);
        foreach ($membresias->items() as $membresia) {
            $saldo = $this->clienteMembresiaService->obtenerSaldoPendiente($membresia->id);
            if ($saldo <= 0) {
                continue;
            }

            $items->push([
                'tipo' => 'membresia',
                'id' => $membresia->id,
                'nombre' => $membresia->membresia?->nombre ?? 'Membresía legacy',
                'saldo_pendiente' => round($saldo, 2),
                'estado' => $membresia->estado,
                'es_vencida' => $this->contratoEstaVencido($membresia->fecha_fin, $membresia->estado),
                'fecha_vencimiento' => $membresia->fecha_fin,
                'detalle' => 'Membresía legacy',
            ]);
        }

        $installments = EnrollmentInstallment::query()
            ->whereHas('plan', fn ($query) => $query->where('cliente_id', $clienteId))
            ->with(['plan.clienteMatricula', 'clienteMatricula'])
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->get();

        foreach ($installments as $installment) {
            $matriculaNombre = $installment->clienteMatricula?->nombre
                ?? $installment->plan?->clienteMatricula?->nombre;

            $items->push([
                'tipo' => 'cuota',
                'id' => $installment->id,
                'cliente_matricula_id' => $installment->cliente_matricula_id,
                'nombre' => 'Cuota '.$installment->numero_cuota.($matriculaNombre ? ' - '.$matriculaNombre : ''),
                'saldo_pendiente' => round((float) $installment->saldo_pendiente, 2),
                'estado' => $installment->estado,
                'es_vencida' => $installment->estaVencida(),
                'fecha_vencimiento' => $installment->fecha_vencimiento,
                'detalle' => 'Plan de cuotas',
            ]);
        }

        $clientDebts = ClientDebt::query()
            ->where('cliente_id', $clienteId)
            ->pendientes()
            ->with('venta')
            ->orderByRaw('CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END')
            ->orderBy('fecha_vencimiento')
            ->orderByDesc('fecha_registro')
            ->get();

        foreach ($clientDebts as $debt) {
            $isOverdue = $debt->fecha_vencimiento?->isPast() ?? false;
            $bucket = $debt->operationalBucket();

            $items->push([
                'tipo' => $bucket === 'membresia' ? 'client_debt_membership' : 'client_debt',
                'id' => $debt->id,
                'nombre' => trim($debt->operationalOriginLabel().' '.($debt->venta?->numero_venta ?? '')),
                'saldo_pendiente' => round((float) $debt->saldo_pendiente, 2),
                'estado' => $debt->estado,
                'es_vencida' => $isOverdue || $debt->estado === 'vencido',
                'fecha_vencimiento' => $debt->fecha_vencimiento,
                'detalle' => $debt->operationalDetailLabel(),
            ]);
        }

        return $items->values();
    }

    public function summarizeCliente(int $clienteId): array
    {
        $items = $this->actionableItemsForCliente($clienteId);

        $overdueItem = $items
            ->filter(fn (array $item) => (bool) ($item['es_vencida'] ?? false))
            ->sortBy('fecha_vencimiento')
            ->first();

        $nextDueItem = $items
            ->filter(fn (array $item) => ! empty($item['fecha_vencimiento']))
            ->sortBy('fecha_vencimiento')
            ->first();

        return [
            'items' => $items->values()->all(),
            'total_pendiente' => round((float) $items->sum('saldo_pendiente'), 2),
            'cantidad_items' => $items->count(),
            'tiene_deuda' => $items->isNotEmpty(),
            'tiene_deuda_vencida' => $overdueItem !== null,
            'proximo_vencimiento' => $nextDueItem['fecha_vencimiento'] ?? null,
            'proximo_item' => $nextDueItem,
            'primer_item_vencido' => $overdueItem,
        ];
    }

    /**
     * @return Collection<int, Cliente>
     */
    public function clientesConDeuda(int $limit = 100): Collection
    {
        $clienteIds = collect()
            ->merge(
                ClientDebt::query()
                    ->pendientes()
                    ->pluck('cliente_id')
            )
            ->merge(
                EnrollmentInstallment::query()
                    ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
                    ->whereHas('plan')
                    ->with('plan:id,cliente_id')
                    ->get()
                    ->pluck('plan.cliente_id')
            )
            ->merge(
                \App\Models\Core\Pago::query()
                    ->where('saldo_pendiente', '>', 0)
                    ->pluck('cliente_id')
            )
            ->filter()
            ->unique()
            ->take($limit * 3)
            ->values();

        if ($clienteIds->isEmpty()) {
            return collect();
        }

        return Cliente::query()
            ->whereIn('id', $clienteIds)
            ->orderBy('nombres')
            ->limit($limit)
            ->get()
            ->map(function (Cliente $cliente) {
                $summary = $this->summarizeCliente($cliente->id);
                $cliente->setAttribute('operacion_diaria_deuda_total', $summary['total_pendiente']);
                $cliente->setAttribute('operacion_diaria_tiene_vencida', $summary['tiene_deuda_vencida']);

                return $cliente;
            })
            ->filter(fn (Cliente $cliente) => (float) $cliente->operacion_diaria_deuda_total > 0)
            ->values();
    }

    protected function contratoEstaVencido(?\Carbon\CarbonInterface $fechaFin, ?string $estado): bool
    {
        $estadoNormalizado = strtolower((string) $estado);

        if (in_array($estadoNormalizado, ['vencida', 'vencido'], true)) {
            return true;
        }

        return $fechaFin !== null && $fechaFin->isPast();
    }
}
