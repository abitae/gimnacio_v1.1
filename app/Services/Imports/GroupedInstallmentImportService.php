<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\CuotaClienteRowData;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Services\EnrollmentInstallmentService;
use App\Support\Imports\CuotaInstallmentGrouper;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GroupedInstallmentImportService
{
    public function __construct(
        private readonly ImportRelationResolverService $resolver,
        private readonly EnrollmentInstallmentService $installmentService,
    ) {}

    /**
     * @param  list<CuotaClienteRowData>  $rows
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    public function process(
        array $rows,
        int $sucursalId,
        int $userId,
        bool $execute,
        bool $stopOnAnyError = false
    ): array {
        $groups = CuotaInstallmentGrouper::groupOrdered($rows);
        $errorsByRow = $this->computeRowErrors($groups, $sucursalId);

        if (! $execute) {
            return $this->buildPreview($rows, $errorsByRow, $sucursalId);
        }

        return $this->buildExecute($rows, $groups, $errorsByRow, $sucursalId, $stopOnAnyError);
    }

    /**
     * @param  array<string, list<CuotaClienteRowData>>  $groups
     * @return array<int, list<string>>
     */
    private function computeRowErrors(array $groups, int $sucursalId): array
    {
        $errorsByRow = [];
        foreach ($groups as $groupRows) {
            $errs = $this->validateGroup($groupRows, $sucursalId);
            foreach ($groupRows as $r) {
                $errorsByRow[$r->rowNumber] = $errs;
            }
        }

        return $errorsByRow;
    }

    /**
     * @param  list<CuotaClienteRowData>  $rows
     * @param  array<int, list<string>>  $errorsByRow
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    private function buildPreview(array $rows, array $errorsByRow, int $sucursalId): array
    {
        $rowResults = [];
        $summary = [
            'total' => count($rows),
            'validas' => 0,
            'errores' => 0,
            'importadas' => 0,
        ];

        foreach ($rows as $row) {
            $errors = $errorsByRow[$row->rowNumber] ?? [];
            if ($errors !== []) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => $errors, 'modelo_id' => null];

                continue;
            }

            $summary['validas']++;
            $summary['importadas']++;
            $cliente = $this->resolver->resolverClientePorCodigoODocumento($row->codigo, $row->dni, $sucursalId, 'DNI');
            $plan = $cliente ? $this->installmentService->activePlanForClienteId($cliente->id) : null;
            $rowResults[] = [
                'fila' => $row->rowNumber,
                'estado' => 'valid',
                'errores' => [],
                'modelo_id' => null,
                'info' => $plan === null
                    ? 'Se creará el plan de cuotas y la deuda cuotificada al confirmar.'
                    : 'Grupo: deuda cuotificada + cuotas vinculadas al confirmar.',
            ];
        }

        return ['summary' => $summary, 'row_results' => $rowResults];
    }

    /**
     * @param  list<CuotaClienteRowData>  $rows
     * @param  array<string, list<CuotaClienteRowData>>  $groups
     * @param  array<int, list<string>>  $errorsByRow
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    private function buildExecute(
        array $rows,
        array $groups,
        array $errorsByRow,
        int $sucursalId,
        bool $stopOnAnyError
    ): array {
        $rowResults = [];
        $summary = [
            'total' => count($rows),
            'validas' => 0,
            'errores' => 0,
            'importadas' => 0,
        ];

        foreach ($rows as $row) {
            $errors = $errorsByRow[$row->rowNumber] ?? [];
            if ($errors !== []) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => $errors, 'modelo_id' => null];
                if ($stopOnAnyError) {
                    return ['summary' => $summary, 'row_results' => $rowResults];
                }

                continue;
            }

            $summary['validas']++;
            $rowResults[] = [
                'fila' => $row->rowNumber,
                'estado' => 'pending',
                'errores' => [],
                'modelo_id' => null,
            ];
        }

        $byFila = [];
        foreach ($rowResults as $idx => $rr) {
            $byFila[(int) ($rr['fila'] ?? 0)] = $idx;
        }

        foreach ($groups as $groupRows) {
            if ($this->groupHasError($groupRows, $errorsByRow)) {
                continue;
            }

            try {
                DB::transaction(function () use ($groupRows, $sucursalId, $byFila, &$rowResults, &$summary): void {
                    $this->persistGroup($groupRows, $sucursalId, $byFila, $rowResults, $summary);
                });
            } catch (\Throwable $e) {
                foreach ($groupRows as $r) {
                    $this->patchRowResult($byFila, $rowResults, $r->rowNumber, 'error', [$e->getMessage()], null);
                    $summary['validas'] = max(0, $summary['validas'] - 1);
                    $summary['errores']++;
                }
                if ($stopOnAnyError) {
                    throw $e;
                }
            }
        }

        foreach ($rowResults as &$rr) {
            if (($rr['estado'] ?? '') === 'pending') {
                $rr['estado'] = 'error';
                $rr['errores'] = ['No se pudo completar la importación del grupo.'];
                $summary['errores']++;
            }
        }
        unset($rr);

        usort($rowResults, fn (array $a, array $b): int => ((int) ($a['fila'] ?? 0)) <=> ((int) ($b['fila'] ?? 0)));

        return ['summary' => $summary, 'row_results' => $rowResults];
    }

    /**
     * @param  list<CuotaClienteRowData>  $groupRows
     * @param  array<int, list<string>>  $errorsByRow
     */
    private function groupHasError(array $groupRows, array $errorsByRow): bool
    {
        foreach ($groupRows as $r) {
            if (($errorsByRow[$r->rowNumber] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int, int>  $byFila
     * @param  list<array<string, mixed>>  $rowResults
     * @param  array<string, int>  $summary
     */
    private function patchRowResult(array $byFila, array &$rowResults, int $fila, string $estado, array $errores, ?int $modeloId): void
    {
        $idx = $byFila[$fila] ?? null;
        if ($idx === null) {
            return;
        }
        $rowResults[$idx]['estado'] = $estado;
        $rowResults[$idx]['errores'] = $errores;
        $rowResults[$idx]['modelo_id'] = $modeloId;
    }

    /**
     * @param  list<CuotaClienteRowData>  $groupRows
     * @param  array<int, int>  $byFila
     * @param  list<array<string, mixed>>  $rowResults
     * @param  array<string, int>  $summary
     */
    private function persistGroup(array $groupRows, int $sucursalId, array $byFila, array &$rowResults, array &$summary): void
    {
        $first = $groupRows[0];
        $cliente = $this->resolver->resolverClientePorCodigoODocumento($first->codigo, $first->dni, $sucursalId, 'DNI');
        if (! $cliente instanceof Cliente) {
            throw new \RuntimeException('Cliente no encontrado.');
        }

        $matricula = $this->findMatriculaOrCreateFallback($cliente, $first, $sucursalId, true);
        if (! $matricula) {
            throw new \RuntimeException('Matrícula no encontrada.');
        }

        $precio = $this->uniqueFloatOrFail($groupRows, fn (CuotaClienteRowData $r) => $r->precio, 'PRECIO');
        $pago = $this->uniqueFloatOrFail($groupRows, fn (CuotaClienteRowData $r) => $r->pago, 'PAGO', allowNullDefault: true, nullAs: 0.0);
        $debe = $this->uniqueFloatOrFail($groupRows, fn (CuotaClienteRowData $r) => $r->debe, 'DEBE', allowNullDefault: true, nullAs: null);

        if ($debe === null) {
            $debe = round(max(0, $precio - $pago), 2);
        }

        $referencia = trim((string) $first->membresia);
        $debt = ClientDebt::query()
            ->where('cliente_id', $cliente->id)
            ->where('sucursal_id', $sucursalId)
            ->where('tipo_deuda', 'cuotificada')
            ->whereRaw('LOWER(TRIM(COALESCE(referencia, ""))) = ?', [mb_strtolower($referencia)])
            ->when($first->fechaInicio, fn ($q) => $q->whereDate('plan_fecha_inicio', $first->fechaInicio->toDateString()))
            ->when($first->fechaFin, fn ($q) => $q->whereDate('plan_fecha_fin', $first->fechaFin->toDateString()))
            ->first();

        $saldo = round((float) $debe, 2);
        $montoPagado = round((float) $pago, 2);
        $montoTotal = round((float) $precio, 2);
        $estadoDeuda = $saldo <= 0 ? 'pagado' : ($montoPagado > 0 ? 'parcial' : 'pendiente');

        $sorted = $groupRows;
        usort($sorted, fn (CuotaClienteRowData $a, CuotaClienteRowData $b): int => ($a->fechaCuota?->timestamp ?? 0) <=> ($b->fechaCuota?->timestamp ?? 0));

        $scheduleTotal = round((float) collect($sorted)->sum(fn (CuotaClienteRowData $row) => (float) ($row->montoCuota ?? 0)), 2);
        $scheduleMatchesFinancedBalance = $this->amountsEqual($scheduleTotal, $saldo)
            && $this->amountsEqual($scheduleTotal + $montoPagado, $montoTotal);

        $installmentStatuses = $scheduleMatchesFinancedBalance
            ? $this->resolveInstallmentStatusesWithoutAppliedPayments($sorted)
            : $this->resolveInstallmentStatuses($sorted, $montoPagado);

        $minCuota = $sorted[0]->fechaCuota ?? CarbonImmutable::now();
        $maxCuota = $sorted[array_key_last($sorted)]->fechaCuota ?? $minCuota;

        $matricula->update([
            'precio_lista' => $montoTotal,
            'precio_final' => $montoTotal,
            'modalidad_pago' => 'cuotas',
            'requiere_plan_cuotas' => true,
            'cuota_inicial_monto' => $scheduleMatchesFinancedBalance ? $montoPagado : 0,
        ]);

        if (! $debt) {
            $debt = ClientDebt::query()->create([
                'cliente_id' => $cliente->id,
                'sucursal_id' => $sucursalId,
                'venta_id' => null,
                'origen_tipo' => 'MEMBRESIA',
                'origen_id' => null,
                'tipo_deuda' => 'cuotificada',
                'referencia' => mb_substr($referencia, 0, 255),
                'plan_fecha_inicio' => $first->fechaInicio?->toDateString(),
                'plan_fecha_fin' => $first->fechaFin?->toDateString(),
                'monto_total' => $montoTotal,
                'monto_pagado' => $montoPagado,
                'saldo_pendiente' => max(0, $saldo),
                'fecha_registro' => $minCuota->toDateString(),
                'fecha_vencimiento' => $first->fechaFin?->toDateString() ?? $maxCuota->toDateString(),
                'estado' => $estadoDeuda,
                'observaciones' => 'Import Excel legacy (cuotas agrupadas).',
            ]);
        } else {
            $debt->update([
                'monto_total' => $montoTotal,
                'monto_pagado' => $montoPagado,
                'saldo_pendiente' => max(0, $saldo),
                'fecha_registro' => $minCuota->toDateString(),
                'fecha_vencimiento' => $first->fechaFin?->toDateString() ?? $maxCuota->toDateString(),
                'estado' => $estadoDeuda,
                'plan_fecha_inicio' => $first->fechaInicio?->toDateString(),
                'plan_fecha_fin' => $first->fechaFin?->toDateString(),
            ]);
        }

        $plan = EnrollmentInstallmentPlan::query()
            ->where('cliente_id', $cliente->id)
            ->lockForUpdate()
            ->first();

        if (! $plan) {
            $plan = $this->createMinimalPlanForLegacyImport($cliente, $matricula, $first);
        }

        $newDates = [];
        foreach ($sorted as $row) {
            if ($row->fechaCuota !== null) {
                $newDates[] = $row->fechaCuota->toDateString();
            }
        }

        if ($newDates !== []) {
            EnrollmentInstallment::query()
                ->where('client_debt_id', $debt->id)
                ->whereNotIn('fecha_vencimiento', $newDates)
                ->delete();
        }

        foreach ($sorted as $idx => $row) {
            if ($row->montoCuota === null || $row->fechaCuota === null) {
                continue;
            }

            $numero = $idx + 1;
            $estado = $installmentStatuses[$idx] ?? $this->resolveInstallmentEstadoFallback($row);

            $inst = EnrollmentInstallment::query()
                ->where('client_debt_id', $debt->id)
                ->whereDate('fecha_vencimiento', $row->fechaCuota->toDateString())
                ->first();

            $payload = [
                'enrollment_installment_plan_id' => $plan->id,
                'cliente_matricula_id' => $matricula->id,
                'client_debt_id' => $debt->id,
                'numero_cuota' => $numero,
                'monto' => $row->montoCuota,
                'fecha_vencimiento' => $row->fechaCuota->toDateString(),
                'estado' => $estado,
                'payment_method_id' => null,
                'numero_operacion' => null,
                'pago_id' => null,
                'fecha_pago' => in_array($estado, ['pagada', 'parcial'], true) ? $row->fechaCuota->toDateString() : null,
            ];

            if ($inst) {
                $inst->update($payload);
            } else {
                $inst = EnrollmentInstallment::query()->create($payload);
            }

            $this->patchRowResult($byFila, $rowResults, $row->rowNumber, 'imported', [], $inst->id);
        }

        $this->installmentService->syncPlanHeaderFromInstallments($plan->fresh());

        $summary['importadas'] += count($groupRows);
    }

    /**
     * @param  list<CuotaClienteRowData>  $groupRows
     * @return list<string>
     */
    private function validateGroup(array $groupRows, int $sucursalId): array
    {
        foreach ($groupRows as $r) {
            $e = $this->validateRow($r);
            if ($e !== []) {
                return $e;
            }
        }

        $first = $groupRows[0];
        $cliente = $this->resolver->resolverClientePorCodigoODocumento($first->codigo, $first->dni, $sucursalId, 'DNI');
        if (! $cliente) {
            return ['Cliente no encontrado por CODIGO/DNI.'];
        }

        $matricula = $this->findMatriculaOrCreateFallback($cliente, $first, $sucursalId, true);
        if (! $matricula) {
            return ['No hay matrícula/membresía compatible para este cliente y fechas.'];
        }

        try {
            $this->uniqueFloatOrFail($groupRows, fn (CuotaClienteRowData $r) => $r->precio, 'PRECIO');
            $this->uniqueFloatOrFail($groupRows, fn (CuotaClienteRowData $r) => $r->pago, 'PAGO', allowNullDefault: true, nullAs: 0.0);
            $this->uniqueFloatOrFail($groupRows, fn (CuotaClienteRowData $r) => $r->debe, 'DEBE', allowNullDefault: true, nullAs: null);
        } catch (\InvalidArgumentException $e) {
            return [$e->getMessage()];
        }

        foreach ($groupRows as $r) {
            if ($r->montoCuota === null || $r->fechaCuota === null) {
                return ['M. CUOTA y FECHA CUOTA son obligatorias.'];
            }
        }

        return [];
    }

    /**
     * @param  list<CuotaClienteRowData>  $groupRows
     */
    private function uniqueFloatOrFail(
        array $groupRows,
        callable $getter,
        string $label,
        bool $allowNullDefault = false,
        ?float $nullAs = null
    ): mixed {
        $tol = (float) config('importacion.money_tolerance', 0.02);
        $values = [];
        foreach ($groupRows as $r) {
            $v = $getter($r);
            if ($v !== null) {
                $values[] = round((float) $v, 2);
            }
        }

        if ($values === []) {
            if ($allowNullDefault) {
                return $nullAs;
            }
            throw new \InvalidArgumentException("{$label} requerido en el grupo de cuotas.");
        }

        $base = $values[0];
        foreach ($values as $v) {
            if (abs($v - $base) > $tol) {
                throw new \InvalidArgumentException("{$label} inconsistente entre filas del grupo.");
            }
        }

        return round((float) $base, 2);
    }

    /**
     * @return list<string>
     */
    private function validateRow(CuotaClienteRowData $row): array
    {
        $errors = [];
        if (! $row->codigo || trim((string) $row->codigo) === '') {
            $errors[] = 'CODIGO obligatorio.';
        }
        if (! $row->membresia || trim((string) $row->membresia) === '') {
            $errors[] = 'MEMBRESIA obligatoria.';
        }

        return $errors;
    }

    private function findMatricula(int $clienteId, string $membresiaNombre, ?CarbonImmutable $fechaInicio, ?CarbonImmutable $fechaFin): ?ClienteMatricula
    {
        $normalized = mb_strtolower(trim($membresiaNombre));

        $baseQuery = ClienteMatricula::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->whereHas('membresia', function ($q) use ($membresiaNombre): void {
                $q->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim($membresiaNombre))]);
            });

        $exactQuery = (clone $baseQuery);
        if ($fechaInicio) {
            $exactQuery->whereDate('fecha_inicio', $fechaInicio->toDateString());
        }
        if ($fechaFin) {
            $exactQuery->whereDate('fecha_fin', $fechaFin->toDateString());
        }

        $exact = $exactQuery->orderByDesc('id')->first();
        if ($exact) {
            return $exact;
        }

        $candidates = (clone $baseQuery)
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        if ($fechaInicio || $fechaFin) {
            $overlap = $candidates->first(function (ClienteMatricula $matricula) use ($fechaInicio, $fechaFin): bool {
                $inicio = $matricula->fecha_inicio?->startOfDay();
                $fin = $matricula->fecha_fin?->startOfDay();

                if (! $inicio || ! $fin) {
                    return false;
                }

                $targetInicio = $fechaInicio?->startOfDay() ?? $inicio;
                $targetFin = $fechaFin?->startOfDay() ?? $fin;

                return $inicio->lte($targetFin) && $fin->gte($targetInicio);
            });

            if ($overlap) {
                return $overlap;
            }
        }

        $nearest = $candidates
            ->map(function (ClienteMatricula $matricula) use ($fechaInicio, $fechaFin): array {
                $score = 0;
                if ($fechaInicio && $matricula->fecha_inicio) {
                    $score += abs($matricula->fecha_inicio->startOfDay()->diffInDays($fechaInicio->startOfDay(), false));
                }
                if ($fechaFin && $matricula->fecha_fin) {
                    $score += abs($matricula->fecha_fin->startOfDay()->diffInDays($fechaFin->startOfDay(), false));
                }

                return ['matricula' => $matricula, 'score' => $score];
            })
            ->sortBy('score')
            ->first();

        if ($nearest && ($nearest['score'] ?? 9999) <= 7) {
            return $nearest['matricula'];
        }

        return $candidates->count() === 1 ? $candidates->first() : null;
    }

    private function findMatriculaOrCreateFallback(Cliente $cliente, CuotaClienteRowData $row, int $sucursalId, bool $allowCreate): ?ClienteMatricula
    {
        $matricula = $this->findMatricula($cliente->id, (string) $row->membresia, $row->fechaInicio, $row->fechaFin);
        if ($matricula || ! $allowCreate) {
            return $matricula;
        }

        $membershipName = trim((string) $row->membresia);
        if ($membershipName === '') {
            return null;
        }

        $membership = $this->resolver->resolverMembresiaPorNombre($membershipName, $sucursalId);
        if (! $membership) {
            $duracionDias = $row->fechaInicio && $row->fechaFin
                ? max(1, $row->fechaInicio->startOfDay()->diffInDays($row->fechaFin->startOfDay()))
                : 30;

            $membership = $this->resolver->crearMembresiaDesdeImportLegacy(
                $membershipName,
                $sucursalId,
                $duracionDias,
                (float) ($row->precio ?? $row->debe ?? 0)
            );
        }

        return ClienteMatricula::query()->create([
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membership->id,
            'fecha_matricula' => $row->fechaInicio?->toDateString(),
            'fecha_inicio' => $row->fechaInicio?->toDateString(),
            'fecha_fin' => $row->fechaFin?->toDateString(),
            'estado' => ($row->fechaFin && $row->fechaFin->startOfDay()->lt(now()->startOfDay())) ? 'vencida' : 'activa',
            'precio_lista' => (float) ($row->precio ?? 0),
            'descuento_monto' => 0,
            'precio_final' => (float) ($row->precio ?? 0),
            'modalidad_pago' => 'cuotas',
            'requiere_plan_cuotas' => true,
            'cuota_inicial_monto' => (float) ($row->pago ?? 0),
            'asesor_id' => null,
            'canal_venta' => 'Importacion cuotas',
            'sucursal_id' => $sucursalId,
        ]);
    }

    private function createMinimalPlanForLegacyImport(Cliente $cliente, ClienteMatricula $matricula, CuotaClienteRowData $row): EnrollmentInstallmentPlan
    {
        $montoCuota = (float) ($row->montoCuota ?? 0);
        $precioMatricula = (float) ($matricula->precio_final ?? 0);
        $fechaInicio = $row->fechaCuota?->toDateString()
            ?? $matricula->fecha_inicio?->format('Y-m-d')
            ?? $matricula->fecha_matricula?->format('Y-m-d')
            ?? now()->format('Y-m-d');

        return EnrollmentInstallmentPlan::query()->create([
            'cliente_id' => $cliente->id,
            'cliente_matricula_id' => null,
            'monto_total' => max($montoCuota, $precioMatricula, 0.01),
            'numero_cuotas' => 1,
            'monto_cuota' => $montoCuota > 0 ? $montoCuota : max($precioMatricula, 0.01),
            'frecuencia' => 'mensual',
            'fecha_inicio' => $fechaInicio,
            'observaciones' => 'Plan creado automáticamente por importación Excel legacy (cuotas).',
        ]);
    }

    /**
     * @param  list<CuotaClienteRowData>  $sortedRows
     * @return list<string>
     */
    private function resolveInstallmentStatuses(array $sortedRows, float $montoPagado): array
    {
        $statuses = [];
        $remaining = round(max(0, $montoPagado), 2);

        foreach ($sortedRows as $row) {
            $monto = round((float) ($row->montoCuota ?? 0), 2);
            if ($monto <= 0) {
                $statuses[] = $this->resolveInstallmentEstadoFallback($row);

                continue;
            }

            if ($remaining >= $monto - 0.009) {
                $statuses[] = 'pagada';
                $remaining = round(max(0, $remaining - $monto), 2);

                continue;
            }

            if ($remaining > 0.009) {
                $statuses[] = 'parcial';
                $remaining = 0.0;

                continue;
            }

            $statuses[] = $this->resolveInstallmentEstadoFallback($row);
        }

        return $statuses;
    }

    private function resolveInstallmentEstadoFallback(CuotaClienteRowData $row): string
    {
        if ($row->fechaCuota && $row->fechaCuota->isPast()) {
            return 'vencida';
        }

        return 'pendiente';
    }

    /**
     * @param  list<CuotaClienteRowData>  $sortedRows
     * @return list<string>
     */
    private function resolveInstallmentStatusesWithoutAppliedPayments(array $sortedRows): array
    {
        return array_map(fn (CuotaClienteRowData $row): string => $this->resolveInstallmentEstadoFallback($row), $sortedRows);
    }

    private function amountsEqual(float $left, float $right): bool
    {
        return abs(round($left, 2) - round($right, 2)) <= 0.02;
    }
}
