<?php

namespace App\Services;

use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Pago;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EnrollmentInstallmentService
{
    public function activePlanForClienteId(int $clienteId): ?EnrollmentInstallmentPlan
    {
        return EnrollmentInstallmentPlan::query()->where('cliente_id', $clienteId)->first();
    }

    /**
     * Añade un tramo financiado al plan único del cliente (crea el plan si no existe).
     */
    public function addFinancing(Cliente $cliente, ClienteMatricula $origen, array $data): EnrollmentInstallmentPlan
    {
        $montoFinanciado = round((float) ($data['monto_total'] ?? 0), 2);
        $numeroCuotas = (int) ($data['numero_cuotas'] ?? 0);
        $cuotaInicialMonto = round((float) ($data['cuota_inicial_monto'] ?? 0), 2);

        if ($montoFinanciado <= 0 || $numeroCuotas < 2) {
            throw new \InvalidArgumentException('Monto financiado y número de cuotas no son válidos.');
        }
        if ($cuotaInicialMonto < 0 || $cuotaInicialMonto >= $montoFinanciado) {
            throw new \InvalidArgumentException('La cuota inicial debe ser mayor o igual a 0 y menor al monto total.');
        }

        if ((int) $origen->cliente_id !== (int) $cliente->id) {
            throw new \InvalidArgumentException('La matrícula no pertenece al cliente indicado.');
        }

        $validated = [
            'frecuencia' => $data['frecuencia'] ?? 'mensual',
            'fecha_inicio' => isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio']) : Carbon::today(),
            'observaciones' => $data['observaciones'] ?? null,
        ];

        $saldoProgramado = round($montoFinanciado - $cuotaInicialMonto, 2);
        $montosCuotas = $this->distribuirMontosExactos($saldoProgramado, $numeroCuotas);

        return DB::transaction(function () use ($cliente, $origen, $numeroCuotas, $cuotaInicialMonto, $montosCuotas, $validated) {
            $plan = EnrollmentInstallmentPlan::query()
                ->where('cliente_id', $cliente->id)
                ->lockForUpdate()
                ->first();

            if (! $plan) {
                $plan = EnrollmentInstallmentPlan::create([
                    'cliente_id' => $cliente->id,
                    'cliente_matricula_id' => null,
                    'monto_total' => 0,
                    'numero_cuotas' => 0,
                    'monto_cuota' => 0,
                    'frecuencia' => $validated['frecuencia'],
                    'fecha_inicio' => $validated['fecha_inicio']->toDateString(),
                    'observaciones' => $validated['observaciones'],
                ]);
            }

            $fechas = $this->generarFechasVencimiento(
                $validated['fecha_inicio'],
                $numeroCuotas,
                $validated['frecuencia'],
                $cuotaInicialMonto > 0
            );

            if ($cuotaInicialMonto > 0) {
                EnrollmentInstallment::create([
                    'enrollment_installment_plan_id' => $plan->id,
                    'cliente_matricula_id' => $origen->id,
                    'numero_cuota' => 0,
                    'monto' => $cuotaInicialMonto,
                    'fecha_vencimiento' => $validated['fecha_inicio'],
                    'estado' => 'pendiente',
                ]);
            }

            foreach ($fechas as $i => $fecha) {
                EnrollmentInstallment::create([
                    'enrollment_installment_plan_id' => $plan->id,
                    'cliente_matricula_id' => $origen->id,
                    'numero_cuota' => 0,
                    'monto' => $montosCuotas[$i],
                    'fecha_vencimiento' => $fecha,
                    'estado' => 'pendiente',
                ]);
            }

            $this->syncPlanHeaderFromInstallments($plan->fresh());

            return $plan->fresh('installments');
        });
    }

    /**
     * Crear primer cronograma desde UI (matrícula asociada al tramo). Equivale a addFinancing.
     *
     * @deprecated Usar addFinancing; se mantiene por compatibilidad con llamadas existentes.
     */
    public function createPlan(ClienteMatricula $clienteMatricula, array $data): EnrollmentInstallmentPlan
    {
        if ($clienteMatricula->enrollmentInstallments()->exists()) {
            throw new \InvalidArgumentException('Esta matrícula ya tiene cuotas registradas en el plan del cliente.');
        }

        $cliente = $clienteMatricula->cliente ?? Cliente::findOrFail($clienteMatricula->cliente_id);

        return $this->addFinancing($cliente, $clienteMatricula, array_merge($data, [
            'monto_total' => (float) ($data['monto_total'] ?? $clienteMatricula->precio_final),
            'fecha_inicio' => $data['fecha_inicio'] ?? $clienteMatricula->fecha_matricula?->format('Y-m-d') ?? now()->format('Y-m-d'),
        ]));
    }

    public function syncPlanHeaderFromInstallments(EnrollmentInstallmentPlan $plan): void
    {
        $rows = $plan->installments()->orderBy('fecha_vencimiento')->orderBy('id')->get();

        foreach ($rows as $index => $row) {
            if ((int) $row->numero_cuota !== $index + 1) {
                $row->update(['numero_cuota' => $index + 1]);
            }
        }

        $sum = (float) $plan->installments()->sum('monto');
        $count = (int) $plan->installments()->count();
        $minFecha = $plan->installments()->min('fecha_vencimiento');

        $plan->update([
            'monto_total' => round($sum, 2),
            'numero_cuotas' => $count,
            'monto_cuota' => $count > 0 ? round($sum / $count, 2) : 0,
            'fecha_inicio' => $minFecha
                ? Carbon::parse($minFecha)->toDateString()
                : ($plan->fecha_inicio?->format('Y-m-d') ?? now()->toDateString()),
        ]);
    }

    /**
     * Intervalos en días (convención comercial): quincenal 15, mensual 30, anual 360.
     * Semanal usa 7 días; personalizado usa el mismo paso que mensual (30).
     *
     * @return array<int, Carbon>
     */
    private function generarFechasVencimiento(Carbon $fechaInicio, int $numeroCuotas, string $frecuencia, bool $desdeSiguienteIntervalo = false): array
    {
        $fechas = [];
        $current = $fechaInicio->copy()->startOfDay();
        if ($desdeSiguienteIntervalo) {
            $current = $this->sumarIntervaloSegunFrecuencia($current, $frecuencia);
        }
        $dias = match ($frecuencia) {
            'semanal' => 7,
            'quincenal' => 15,
            'mensual' => 30,
            'anual' => 360,
            'personalizado' => 30,
            default => 30,
        };

        for ($i = 0; $i < $numeroCuotas; $i++) {
            $fechas[] = $current->copy();
            if ($i < $numeroCuotas - 1) {
                $current->addDays($dias);
            }
        }

        return $fechas;
    }

    /**
     * Distribuye un monto en n cuotas con precisión de centavos y suma exacta.
     *
     * @return array<int, float>
     */
    private function distribuirMontosExactos(float $montoTotal, int $numeroCuotas): array
    {
        if ($numeroCuotas <= 0) {
            return [];
        }

        $totalCentavos = (int) round($montoTotal * 100);
        $baseCentavos = intdiv($totalCentavos, $numeroCuotas);
        $residuo = $totalCentavos % $numeroCuotas;
        $montos = [];

        for ($i = 0; $i < $numeroCuotas; $i++) {
            $extra = $i < $residuo ? 1 : 0;
            $montos[] = ($baseCentavos + $extra) / 100;
        }

        return $montos;
    }

    private function sumarIntervaloSegunFrecuencia(Carbon $fecha, string $frecuencia): Carbon
    {
        $dias = match ($frecuencia) {
            'semanal' => 7,
            'quincenal' => 15,
            'mensual' => 30,
            'anual' => 360,
            'personalizado' => 30,
            default => 30,
        };

        return $fecha->addDays($dias);
    }

    public function pagarCuota(EnrollmentInstallment $installment, array $data): Pago
    {
        if (! in_array($installment->estado, ['pendiente', 'vencida', 'parcial'], true)) {
            throw new \InvalidArgumentException('Esta cuota ya está pagada.');
        }

        return DB::transaction(function () use ($installment, $data) {
            $plan = $installment->plan()->lockForUpdate()->first();
            if (! $plan) {
                throw new \InvalidArgumentException('Plan de cuotas no encontrado.');
            }

            $matriculaId = $installment->cliente_matricula_id ?? $plan->cliente_matricula_id;
            $matricula = $matriculaId ? ClienteMatricula::find($matriculaId) : null;
            if (! $matricula) {
                throw new \InvalidArgumentException('La cuota no tiene una matrícula asociada para registrar el pago.');
            }

            $monto = (float) ($data['monto'] ?? $installment->monto);
            $cajaService = app(CajaService::class);

            if ($matricula->estado === 'cancelada') {
                throw new \InvalidArgumentException('No se pueden cobrar cuotas de una matrícula cancelada.');
            }

            if (round($monto, 2) !== round((float) $installment->monto, 2)) {
                throw new \InvalidArgumentException('Por ahora solo se admite el pago completo de la cuota programada.');
            }

            if (! $cajaService->validarCajaAbierta(auth()->id())) {
                throw new \InvalidArgumentException('No hay una caja abierta. Abra una caja antes de registrar el pago de cuota.');
            }

            $caja = ! empty($data['caja_id'])
                ? \App\Models\Core\Caja::findOrFail((int) $data['caja_id'])
                : $cajaService->obtenerOCrearCajaAbierta();
            $saldoPendienteActual = app(ClienteMatriculaService::class)->obtenerSaldoPendiente($matricula->id);
            $saldoPendienteNuevo = max(0, $saldoPendienteActual - $monto);

            $metodoPago = $data['metodo_pago'] ?? ('Cuota '.$installment->numero_cuota);
            $paymentMethodId = $data['payment_method_id'] ?? null;
            if ($paymentMethodId) {
                $pm = \App\Models\Core\PaymentMethod::find($paymentMethodId);
                if ($pm) {
                    $metodoPago = $pm->nombre;
                }
            }

            $cobro = app(CobroTicketService::class)->resolverComprobantePago([
                'comprobante_tipo' => $data['comprobante_tipo'] ?? null,
                'comprobante_numero' => $data['comprobante_numero'] ?? null,
            ]);

            $pago = Pago::create([
                'cliente_id' => $matricula->cliente_id,
                'cliente_matricula_id' => $matricula->id,
                'monto' => $monto,
                'moneda' => $data['moneda'] ?? 'PEN',
                'metodo_pago' => $metodoPago,
                'fecha_pago' => $data['fecha_pago'] ?? now(),
                'payment_method_id' => $paymentMethodId,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'entidad_financiera' => $data['entidad_financiera'] ?? null,
                'es_pago_parcial' => $saldoPendienteNuevo > 0,
                'saldo_pendiente' => $saldoPendienteNuevo,
                'comprobante_tipo' => $cobro['tipo'],
                'comprobante_numero' => $cobro['numero'],
                'caja_id' => $caja->id,
                'registrado_por' => auth()->id(),
            ]);

            $obsCaja = 'Método de pago: '.$metodoPago;
            if ($pago->comprobante_tipo || $pago->comprobante_numero) {
                $obsCaja .= ', Comprobante: '.strtoupper((string) $pago->comprobante_tipo).' '.$pago->comprobante_numero;
            }

            $cajaService->registrarIngresoPorPago(
                $pago,
                'Pago cuota '.$installment->numero_cuota.' - '.$matricula->nombre,
                CajaMovimiento::CATEGORIA_CUOTA,
                CajaMovimiento::ORIGEN_ENROLLMENT_INSTALLMENTS,
                null,
                null,
                trim($obsCaja, ', ')
            );

            $installment->update([
                'estado' => 'pagada',
                'payment_method_id' => $data['payment_method_id'] ?? null,
                'numero_operacion' => $data['numero_operacion'] ?? null,
                'pago_id' => $pago->id,
                'fecha_pago' => $pago->fecha_pago ? Carbon::parse($pago->fecha_pago)->toDateString() : null,
            ]);

            $plan->installments()
                ->where('estado', 'pendiente')
                ->where('fecha_vencimiento', '<', now()->toDateString())
                ->update(['estado' => 'vencida']);

            return $pago;
        });
    }

    public function firstPayableInstallmentForMatricula(int $clienteMatriculaId): ?EnrollmentInstallment
    {
        return EnrollmentInstallment::query()
            ->where('cliente_matricula_id', $clienteMatriculaId)
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, EnrollmentInstallment>
     */
    public function installmentsForMatricula(int $clienteMatriculaId): \Illuminate\Support\Collection
    {
        return EnrollmentInstallment::query()
            ->where('cliente_matricula_id', $clienteMatriculaId)
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->get();
    }

    /**
     * @return \Illuminate\Support\Collection<int, EnrollmentInstallment>
     */
    public function installmentsForCliente(int $clienteId): \Illuminate\Support\Collection
    {
        return EnrollmentInstallment::query()
            ->whereHas('plan', fn ($q) => $q->where('cliente_id', $clienteId))
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->get();
    }

    /**
     * Aplaza vencimientos de cuotas no pagadas ligadas a una matrícula.
     */
    public function shiftPendingInstallmentsForMatricula(ClienteMatricula $matricula, int $dias): void
    {
        if ($dias <= 0) {
            return;
        }

        $rows = EnrollmentInstallment::query()
            ->where('cliente_matricula_id', $matricula->id)
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->orderBy('id')
            ->get();

        foreach ($rows as $i) {
            $i->update([
                'fecha_vencimiento' => Carbon::parse($i->fecha_vencimiento)->addDays($dias)->toDateString(),
            ]);
        }

        $plan = EnrollmentInstallmentPlan::query()->where('cliente_id', $matricula->cliente_id)->first();
        if ($plan) {
            $this->syncPlanHeaderFromInstallments($plan);
        }
    }
}
