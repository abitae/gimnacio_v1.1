<?php

namespace App\Services;

use App\Models\Core\Caja;
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
        $scheduleInput = $this->normalizeScheduleRows($data['schedule'] ?? []);
        $sumInput = round((float) collect($scheduleInput)->sum('monto'), 2);
        $cuotaInicialMonto = round((float) ($data['cuota_inicial_monto'] ?? 0), 2);

        if ($montoFinanciado <= 0) {
            throw new \InvalidArgumentException('Monto financiado y número de cuotas no son válidos.');
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

        if ($scheduleInput !== [] && $this->scheduleAmountsEqual($sumInput, $montoFinanciado)) {
            $schedule = $this->validateFullScheduleRows($scheduleInput, $montoFinanciado);
        } elseif ($scheduleInput !== []) {
            if ($cuotaInicialMonto < 0 || $cuotaInicialMonto >= $montoFinanciado) {
                throw new \InvalidArgumentException('La cuota inicial debe ser mayor o igual a 0 y menor al monto total.');
            }
            if (! $this->scheduleAmountsEqual($sumInput, $saldoProgramado)) {
                throw new \InvalidArgumentException('La suma del cronograma debe coincidir con el saldo pendiente (precio menos cuota inicial).');
            }
            $schedule = $this->validateScheduleRows($scheduleInput, $saldoProgramado);
        } else {
            $numeroCuotas = (int) ($data['numero_cuotas'] ?? 0);
            if ($numeroCuotas < 2) {
                throw new \InvalidArgumentException('Monto financiado y número de cuotas no son válidos.');
            }
            if ($cuotaInicialMonto < 0 || $cuotaInicialMonto >= $montoFinanciado) {
                throw new \InvalidArgumentException('La cuota inicial debe ser mayor o igual a 0 y menor al monto total.');
            }
            $schedule = $this->previewSchedule([
                'monto_total' => $montoFinanciado,
                'numero_cuotas' => $numeroCuotas,
                'frecuencia' => $validated['frecuencia'],
                'fecha_inicio' => $validated['fecha_inicio']->toDateString(),
                'cuota_inicial_monto' => $cuotaInicialMonto,
            ]);
        }

        $numeroCuotas = count($schedule);
        if ($numeroCuotas < 2) {
            throw new \InvalidArgumentException('Monto financiado y número de cuotas no son válidos.');
        }

        return DB::transaction(function () use ($cliente, $origen, $schedule, $validated) {
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

            foreach ($schedule as $i => $row) {
                EnrollmentInstallment::create([
                    'enrollment_installment_plan_id' => $plan->id,
                    'cliente_matricula_id' => $origen->id,
                    'numero_cuota' => 0,
                    'monto' => $row['monto'],
                    'fecha_vencimiento' => $row['fecha_vencimiento'],
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

    public function previewSchedule(array $data): array
    {
        $montoTotal = round((float) ($data['monto_total'] ?? 0), 2);
        $cuotaInicialMonto = round((float) ($data['cuota_inicial_monto'] ?? 0), 2);
        $numeroCuotas = (int) ($data['numero_cuotas'] ?? 0);
        $frecuencia = (string) ($data['frecuencia'] ?? 'mensual');
        $fechaInicio = isset($data['fecha_inicio']) ? Carbon::parse($data['fecha_inicio']) : Carbon::today();

        if ($montoTotal <= 0 || $numeroCuotas <= 0) {
            return [];
        }

        $saldoProgramado = round($montoTotal - $cuotaInicialMonto, 2);
        if ($saldoProgramado < 0) {
            throw new \InvalidArgumentException('La cuota inicial no puede ser mayor al monto total.');
        }

        if ($cuotaInicialMonto > 0 && $numeroCuotas < 2) {
            throw new \InvalidArgumentException('Debes indicar al menos 2 cuotas cuando la primera cuota es manual.');
        }

        $schedule = [];

        if ($cuotaInicialMonto > 0) {
            $schedule[] = [
                'numero_cuota' => 1,
                'fecha_vencimiento' => $fechaInicio->copy()->toDateString(),
                'monto' => $cuotaInicialMonto,
            ];
        }

        $cuotasRestantes = $numeroCuotas - count($schedule);
        if ($cuotasRestantes <= 0) {
            return $schedule;
        }

        $montosCuotas = $this->distribuirMontosExactos($saldoProgramado, $cuotasRestantes);
        $fechas = $this->generarFechasVencimiento($fechaInicio, $cuotasRestantes, $frecuencia, $cuotaInicialMonto > 0);

        foreach (collect($fechas)->values() as $index => $fecha) {
            $schedule[] = [
                'numero_cuota' => count($schedule) + 1,
                'fecha_vencimiento' => $fecha->copy()->toDateString(),
                'monto' => round((float) ($montosCuotas[$index] ?? 0), 2),
            ];
        }

        return $schedule;
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
        for ($i = 0; $i < $numeroCuotas; $i++) {
            $fechas[] = $current->copy();
            if ($i < $numeroCuotas - 1) {
                $current = $this->sumarIntervaloSegunFrecuencia($current, $frecuencia);
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
        return match ($frecuencia) {
            'semanal' => $fecha->copy()->addDays(7),
            'quincenal' => $fecha->copy()->addDays(15),
            'mensual' => $this->sumarMesMismoDiaOFinDeMes($fecha),
            'anual' => $fecha->copy()->addDays(360),
            'personalizado' => $fecha->copy()->addDays(30),
            default => $fecha->copy()->addDays(30),
        };
    }

    private function sumarMesMismoDiaOFinDeMes(Carbon $fecha): Carbon
    {
        $fecha = $fecha->copy()->startOfDay();
        $diaObjetivo = (int) $fecha->day;
        $siguienteMes = $fecha->copy()->addMonthNoOverflow()->startOfMonth();
        $ultimoDia = (int) $siguienteMes->copy()->endOfMonth()->day;

        return $siguienteMes->copy()->day(min($diaObjetivo, $ultimoDia));
    }

    private function normalizeScheduleRows(array $rows): array
    {
        return collect($rows)
            ->map(function ($row) {
                return [
                    'fecha_vencimiento' => isset($row['fecha_vencimiento']) ? Carbon::parse($row['fecha_vencimiento'])->toDateString() : null,
                    'monto' => round((float) ($row['monto'] ?? 0), 2),
                ];
            })
            ->filter(fn ($row) => filled($row['fecha_vencimiento']) && $row['monto'] > 0)
            ->values()
            ->all();
    }

    private function validateScheduleRows(array $schedule, float $saldoProgramado): array
    {
        $suma = round((float) collect($schedule)->sum('monto'), 2);
        if (round($suma, 2) !== round($saldoProgramado, 2)) {
            throw new \InvalidArgumentException('La suma del cronograma debe coincidir con el saldo pendiente.');
        }

        return array_values(array_map(function ($row, $index) {
            return [
                'numero_cuota' => $index + 1,
                'fecha_vencimiento' => $row['fecha_vencimiento'],
                'monto' => round((float) $row['monto'], 2),
            ];
        }, $schedule, array_keys($schedule)));
    }

    /**
     * Cronograma completo enviado desde la UI (suma de montos = precio final de la matrícula).
     *
     * @param  array<int, array{fecha_vencimiento: string|null, monto: float}>  $schedule
     * @return array<int, array{numero_cuota: int, fecha_vencimiento: string, monto: float}>
     */
    private function validateFullScheduleRows(array $schedule, float $montoTotal): array
    {
        $suma = round((float) collect($schedule)->sum('monto'), 2);
        if (! $this->scheduleAmountsEqual($suma, $montoTotal)) {
            throw new \InvalidArgumentException('La suma del cronograma debe coincidir con el monto total.');
        }

        return array_values(array_map(function ($row, $index) {
            return [
                'numero_cuota' => $index + 1,
                'fecha_vencimiento' => $row['fecha_vencimiento'],
                'monto' => round((float) $row['monto'], 2),
            ];
        }, $schedule, array_keys($schedule)));
    }

    private function scheduleAmountsEqual(float $a, float $b): bool
    {
        return abs(round($a, 2) - round($b, 2)) < 0.009;
    }

    /**
     * Reparte un monto en N partes con suma exacta en centavos (misma lógica que el cronograma automático).
     *
     * @return array<int, float>
     */
    public function distribuirMontoEnPartesIguales(float $montoTotal, int $partes): array
    {
        return $this->distribuirMontosExactos($montoTotal, $partes);
    }

    /**
     * Cambia el monto de una cuota en estado pendiente y reparte el saldo entre las cuotas pendientes posteriores
     * (misma matrícula y plan), manteniendo la suma del bloque desde esa cuota en adelante.
     */
    public function updatePendienteMontoRedistributeTail(EnrollmentInstallment $installment, float $nuevoMonto): void
    {
        $nuevoMonto = round($nuevoMonto, 2);
        if ($nuevoMonto < 0.01) {
            throw new \InvalidArgumentException(__('El monto debe ser al menos 0.01.'));
        }

        DB::transaction(function () use ($installment, $nuevoMonto) {
            $row = EnrollmentInstallment::query()->whereKey($installment->id)->lockForUpdate()->first();
            if (! $row || $row->estado !== 'pendiente') {
                throw new \InvalidArgumentException(__('Solo se puede modificar el monto de cuotas en estado pendiente.'));
            }

            $planId = (int) $row->enrollment_installment_plan_id;
            $matriculaId = $row->cliente_matricula_id;
            if (! $matriculaId) {
                throw new \InvalidArgumentException(__('La cuota no está asociada a una matrícula.'));
            }

            $pendientes = EnrollmentInstallment::query()
                ->where('enrollment_installment_plan_id', $planId)
                ->where('cliente_matricula_id', $matriculaId)
                ->where('estado', 'pendiente')
                ->orderBy('fecha_vencimiento')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $index = $pendientes->search(fn ($i) => (int) $i->id === (int) $row->id);
            if ($index === false) {
                throw new \InvalidArgumentException(__('Cuota no encontrada en el grupo de pendientes.'));
            }

            $oldSumFrom = round((float) $pendientes->slice($index)->sum('monto'), 2);
            $tail = $pendientes->slice($index + 1);
            if ($tail->isEmpty()) {
                throw new \InvalidArgumentException(__('No hay cuotas pendientes posteriores para redistribuir el saldo. Ajuste el monto total desde matrícula o use el flujo de pago.'));
            }

            $newTailSum = round($oldSumFrom - $nuevoMonto, 2);
            $nTail = $tail->count();
            $minTail = round(0.01 * $nTail, 2);
            if ($newTailSum < $minTail) {
                throw new \InvalidArgumentException(__('El monto deja un saldo insuficiente para las cuotas posteriores (mín. :min).', ['min' => number_format($minTail, 2)]));
            }

            $montos = $this->distribuirMontosExactos($newTailSum, $nTail);

            $row->update(['monto' => $nuevoMonto]);
            foreach ($tail->values() as $i => $t) {
                $t->update(['monto' => $montos[$i]]);
            }

            $plan = EnrollmentInstallmentPlan::query()->whereKey($planId)->lockForUpdate()->first();
            if ($plan) {
                $this->syncPlanHeaderFromInstallments($plan);
            }
        });
    }

    public function pagarCuota(EnrollmentInstallment $installment, array $data): Pago
    {
        if (! in_array($installment->estado, ['pendiente', 'vencida', 'parcial'], true)) {
            throw new \InvalidArgumentException('Esta cuota ya está pagada.');
        }

        return DB::transaction(function () use ($installment, $data) {
            $row = EnrollmentInstallment::query()
                ->whereKey($installment->id)
                ->lockForUpdate()
                ->first();

            if (! $row || ! in_array($row->estado, ['pendiente', 'vencida', 'parcial'], true)) {
                throw new \InvalidArgumentException('Esta cuota ya esta pagada.');
            }

            $plan = $row->plan()->lockForUpdate()->first();
            if (! $plan) {
                throw new \InvalidArgumentException('Plan de cuotas no encontrado.');
            }

            $matriculaId = $row->cliente_matricula_id ?? $plan->cliente_matricula_id;
            $matricula = $matriculaId ? ClienteMatricula::find($matriculaId) : null;
            if (! $matricula) {
                throw new \InvalidArgumentException('La cuota no tiene una matrícula asociada para registrar el pago.');
            }

            $monto = round((float) ($data['monto'] ?? $row->saldo_pendiente), 2);
            $cajaService = app(CajaService::class);

            if ($matricula->estado === 'cancelada') {
                throw new \InvalidArgumentException('No se pueden cobrar cuotas de una matrícula cancelada.');
            }

            $saldoCuota = $row->saldo_pendiente;
            if ($monto <= 0) {
                throw new \InvalidArgumentException('El monto del pago debe ser mayor a cero.');
            }

            if ($monto > $saldoCuota) {
                throw new \InvalidArgumentException('El monto del pago no puede ser mayor al saldo pendiente de la cuota.');
            }

            if (! $cajaService->validarCajaAbierta(auth()->id())) {
                throw new \InvalidArgumentException('No hay una caja abierta. Abra una caja antes de registrar el pago de cuota.');
            }

            $caja = ! empty($data['caja_id'])
                ? \App\Models\Core\Caja::findOrFail((int) $data['caja_id'])
                : $cajaService->obtenerOCrearCajaAbierta();
            $this->assertCajaSucursal($caja->id, (int) $matricula->sucursal_id);
            $saldoPendienteActual = app(ClienteMatriculaService::class)->obtenerSaldoPendiente($matricula->id);
            $saldoPendienteNuevo = max(0, $saldoPendienteActual - $monto);
            $detalleService = app(PagoDetalleService::class);
            $lineasPago = $detalleService->normalizar(
                array_merge(['metodo_pago' => 'Cuota '.$row->numero_cuota], $data),
                $monto,
                (int) $matricula->sucursal_id,
            );
            $datosCabecera = $detalleService->datosCabecera($lineasPago);

            $cobro = app(CobroTicketService::class)->resolverComprobantePago([
                'comprobante_tipo' => $data['comprobante_tipo'] ?? null,
                'comprobante_numero' => $data['comprobante_numero'] ?? null,
            ]);
            $pago = Pago::create([
                'cliente_id' => $matricula->cliente_id,
                'cliente_matricula_id' => $matricula->id,
                'enrollment_installment_id' => $row->id,
                'monto' => $monto,
                'moneda' => $data['moneda'] ?? 'PEN',
                ...$datosCabecera,
                'fecha_pago' => $data['fecha_pago'] ?? now(),
                'es_pago_parcial' => $saldoPendienteNuevo > 0,
                'saldo_pendiente' => $saldoPendienteNuevo,
                'comprobante_tipo' => $cobro['tipo'],
                'comprobante_numero' => $cobro['numero'],
                'caja_id' => $caja->id,
                'registrado_por' => auth()->id(),
                'sucursal_id' => $matricula->sucursal_id,
            ]);
            $detalleService->crearDetalles($pago, $caja, $lineasPago);

            $obsCaja = null;
            if ($pago->comprobante_tipo || $pago->comprobante_numero) {
                $obsCaja = 'Comprobante: '.strtoupper((string) $pago->comprobante_tipo).' '.$pago->comprobante_numero;
            }

            $cajaService->registrarIngresosPorPago(
                $pago,
                'Pago cuota '.$row->numero_cuota.' - '.$matricula->nombre,
                CajaMovimiento::CATEGORIA_CUOTA,
                CajaMovimiento::ORIGEN_ENROLLMENT_INSTALLMENTS,
                $obsCaja,
            );

            $nuevoMontoPagado = round($row->monto_pagado_actual + $monto, 2);
            $nuevoSaldoCuota = round(max(0, (float) $row->monto - $nuevoMontoPagado), 2);
            $nuevoEstado = $nuevoSaldoCuota <= 0
                ? 'pagada'
                : ($row->fecha_vencimiento && Carbon::parse($row->fecha_vencimiento)->isPast() ? 'vencida' : 'parcial');

            $row->update([
                'monto_pagado' => min((float) $row->monto, $nuevoMontoPagado),
                'estado' => $nuevoEstado,
                'payment_method_id' => $datosCabecera['payment_method_id'],
                'numero_operacion' => $datosCabecera['numero_operacion'],
                'pago_id' => $pago->id,
                'fecha_pago' => $pago->fecha_pago ? Carbon::parse($pago->fecha_pago)->toDateString() : null,
            ]);

            $plan->installments()
                ->where('estado', 'pendiente')
                ->where('fecha_vencimiento', '<', now()->toDateString())
                ->update(['estado' => 'vencida']);

            return $pago->fresh(['detalles.paymentMethod']);
        });
    }

    public function firstPayableInstallmentForMatricula(int $clienteMatriculaId): ?EnrollmentInstallment
    {
        return EnrollmentInstallment::query()
            ->where('cliente_matricula_id', $clienteMatriculaId)
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->orderBy('fecha_vencimiento')
            ->orderBy('numero_cuota')
            ->orderBy('id')
            ->first();
    }

    public function isFirstPayableInstallment(EnrollmentInstallment $installment): bool
    {
        return in_array($installment->estado, ['pendiente', 'vencida', 'parcial'], true)
            && (bool) $installment->cliente_matricula_id;
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
            ->orderBy('id')
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
        if ($dias === 0) {
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

    private function assertCajaSucursal(int $cajaId, int $sucursalId): void
    {
        $caja = Caja::findOrFail($cajaId);

        if ((int) $caja->sucursal_id !== $sucursalId) {
            throw new \InvalidArgumentException('La caja seleccionada no pertenece a la sucursal de la matricula.');
        }
    }
}
