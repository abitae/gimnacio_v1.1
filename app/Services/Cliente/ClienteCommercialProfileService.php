<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteCommercialSummary;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Services\ClientEnrollmentService;
use App\Services\EnrollmentInstallmentService;
use App\Services\Legacy\LegacyMembresiaReadService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Estado comercial unificado: matrículas activas, cuotas y membresías legacy (solo lectura).
 */
class ClienteCommercialProfileService
{
    public function __construct(
        protected EnrollmentInstallmentService $enrollmentInstallmentService,
        protected ClientEnrollmentService $clientEnrollmentService,
        protected LegacyMembresiaReadService $legacyMembresiaReadService,
    ) {}

    public function getSummary(int $clienteId, bool $withHistory = true): ClienteCommercialSummary
    {
        $matriculasCliente = ClienteMatricula::query()
            ->where('cliente_id', $clienteId)
            ->with(['membresia', 'clase', 'asesor', 'pagos', 'enrollmentInstallments.pagos', 'enrollmentInstallments.pago'])
            ->orderByDesc('fecha_inicio')
            ->get();

        $usesLegacy = false;
        $historialMembresias = [];
        $historialClases = [];

        if ($withHistory) {
            $memberships = $matriculasCliente
                ->where('tipo', 'membresia')
                ->take(10)
                ->values();

            if ($memberships->isEmpty()) {
                $usesLegacy = true;
                $memberships = $this->legacyMembresiaReadService->historyForCliente($clienteId, 10);
            }

            $historialMembresias = $memberships->all();
            $historialClases = $matriculasCliente
                ->where('tipo', 'clase')
                ->take(10)
                ->values()
                ->all();
        }

        $matriculasOperativas = $matriculasCliente
            ->filter(fn (ClienteMatricula $row) => $row->estado !== null && (string) $row->estado !== 'cancelada')
            ->values();

        $cuotasCliente = $this->enrollmentInstallmentService->installmentsForCliente($clienteId);

        $matriculasSinCronogramaCuotas = $cuotasCliente->isEmpty()
            ? $matriculasOperativas
                ->filter(fn (ClienteMatricula $row) => $row->usaPlanCuotas() && $row->enrollmentInstallments->isEmpty())
                ->values()
            : collect([]);

        $pendienteCuotaPorMatricula = $this->resolvePendienteCuotaPorMatricula($matriculasOperativas);

        $matriculaOpcionesCobro = $matriculasOperativas
            ->filter(fn (ClienteMatricula $row) => ! $row->usaPlanCuotas())
            ->values();

        $matriculasFinancieras = $matriculasOperativas
            ->map(fn (ClienteMatricula $matricula) => $this->buildFinancialMatriculaRow($matricula))
            ->values();

        $deudaPlanesPendiente = round((float) $matriculasFinancieras->sum('saldo_total'), 2);

        $matriculasConCuotas = $matriculasOperativas
            ->filter(fn (ClienteMatricula $matricula) => $matricula->usaPlanCuotas())
            ->map(fn (ClienteMatricula $matricula) => $this->buildInstallmentMatriculaRow($matricula))
            ->values();

        $membresiaActivaFromHistory = null;
        if ($withHistory && ! empty($historialMembresias)) {
            $membresiaActivaFromHistory = $this->clientEnrollmentService
                ->resolveLatestActiveEnrollmentFromHistory(collect($historialMembresias));
        }

        return new ClienteCommercialSummary(
            historialMembresias: $historialMembresias,
            historialClases: $historialClases,
            usesLegacyMembresiasHistory: $usesLegacy,
            matriculaOpcionesCobro: $matriculaOpcionesCobro,
            pendienteCuotaPorMatricula: $pendienteCuotaPorMatricula,
            cuotasCliente: $cuotasCliente,
            matriculasFinancieras: $matriculasFinancieras,
            matriculasConCuotas: $matriculasConCuotas,
            deudaPlanesPendiente: $deudaPlanesPendiente,
            matriculasSinCronogramaCuotas: $matriculasSinCronogramaCuotas,
            membresiaActivaFromHistory: $membresiaActivaFromHistory,
        );
    }

    /**
     * @param  Collection<int, ClienteMatricula>  $matriculas
     * @return array<int, EnrollmentInstallment>
     */
    protected function resolvePendienteCuotaPorMatricula(Collection $matriculas): array
    {
        $matriculaIds = $matriculas
            ->filter(fn (ClienteMatricula $row) => $row->usaPlanCuotas())
            ->pluck('id')
            ->values();

        if ($matriculaIds->isEmpty()) {
            return [];
        }

        return EnrollmentInstallment::query()
            ->whereIn('cliente_matricula_id', $matriculaIds)
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->orderBy('id')
            ->get()
            ->groupBy('cliente_matricula_id')
            ->map(fn (Collection $installments) => $installments->first())
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function buildFinancialMatriculaRow(ClienteMatricula $matricula): array
    {
        $pagadoTotal = round((float) $matricula->monto_pagado_actual, 2);
        $saldoTotal = round((float) $matricula->saldo_pendiente_actual, 2);
        $precioTotal = round((float) $matricula->precio_final, 2);
        $ultimaFechaPago = $this->latestPaymentDateFromCollection($matricula->pagos);

        return [
            'id' => $matricula->id,
            'tipo_label' => $matricula->esMembresia() ? 'Membresía' : 'Clase',
            'estado_matricula' => (string) ($matricula->estado ?? '—'),
            'plan_nombre' => $matricula->nombre,
            'fecha_matricula' => $matricula->fecha_matricula,
            'fecha_ultimo_pago' => $ultimaFechaPago,
            'precio_total' => $precioTotal,
            'pagado_total' => $pagadoTotal,
            'saldo_total' => $saldoTotal,
            'modalidad_pago' => $matricula->usaPlanCuotas() ? 'Cuotas' : 'Contado',
            'usa_plan_cuotas' => $matricula->usaPlanCuotas(),
            'accion_cobrar_habilitada' => ! $matricula->usaPlanCuotas() && $saldoTotal > 0,
            'is_legacy' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildInstallmentMatriculaRow(ClienteMatricula $matricula): array
    {
        $installments = $matricula->enrollmentInstallments
            ->sortBy([
                ['fecha_vencimiento', 'asc'],
                ['numero_cuota', 'asc'],
            ])
            ->values();
        $installments = $installments
            ->map(function ($installment) {
                $estado = (string) ($installment->estado ?? 'pendiente');
                $ultimaFechaPago = $this->latestPaymentDateFromCollection($installment->pagos);

                return [
                    'id' => $installment->id,
                    'numero_cuota' => (int) $installment->numero_cuota,
                    'fecha_vencimiento' => $installment->fecha_vencimiento,
                    'fecha_ultimo_pago' => $ultimaFechaPago
                        ?? ($installment->pago ? $installment->pago->fechaHoraPago() : null)
                        ?? $installment->fecha_pago,
                    'monto' => round((float) $installment->monto, 2),
                    'monto_pagado' => round((float) $installment->monto_pagado_actual, 2),
                    'saldo' => round((float) $installment->saldo_pendiente, 2),
                    'estado' => $estado,
                    'estado_label' => Str::ucfirst($estado),
                    'puede_pagar' => in_array($estado, ['pendiente', 'vencida', 'parcial'], true),
                ];
            });

        $cuotasPendientes = $installments
            ->filter(fn (array $cuota) => in_array($cuota['estado'], ['pendiente', 'vencida', 'parcial'], true))
            ->values();

        return [
            'id' => $matricula->id,
            'tipo_label' => $matricula->esMembresia() ? 'Membresía' : 'Clase',
            'estado_matricula' => (string) ($matricula->estado ?? '—'),
            'plan_nombre' => $matricula->nombre,
            'precio_total' => round((float) $matricula->precio_final, 2),
            'saldo_total' => round((float) $matricula->saldo_pendiente_actual, 2),
            'tiene_cronograma' => $installments->isNotEmpty(),
            'cuotas' => $cuotasPendientes->all(),
            'is_legacy' => false,
        ];
    }

    protected function latestPaymentDateFromCollection($payments)
    {
        $payment = collect($payments)
            ->filter(fn ($payment) => $payment?->fecha_pago)
            ->sortByDesc(fn ($payment) => $payment->fecha_pago?->timestamp ?? 0)
            ->first();

        return $payment?->fechaHoraPago();
    }
}
