<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\ClienteAgrupadoContractRowData;
use App\DataTransferObjects\Imports\ClienteAgrupadoSummaryRowData;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\EnrollmentInstallment;
use App\Models\Core\EnrollmentInstallmentPlan;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use App\Models\User;
use App\Services\EnrollmentInstallmentService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ClientesAgrupadosImportService
{
    private const HISTORICAL_PAYMENT_METHOD = 'Importacion legacy';

    public function __construct(
        private readonly ExcelClientesAgrupadosReader $reader,
        private readonly ImportRelationResolverService $resolver,
        private readonly SellerUserResolver $sellerUserResolver,
        private readonly EnrollmentInstallmentService $installmentService,
    ) {}

    /**
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>, phase_summaries: array<string, array<string, int>>}
     */
    public function process(string $filePath, int $sucursalId, int $userId, bool $execute, bool $stopOnAnyError = false): array
    {
        $data = $this->reader->read($filePath);
        $contracts = $data['contracts'];
        $summaries = $data['summaries'];
        $summaryByCode = $this->summariesByCode($summaries);

        $rowResults = [];
        $general = [
            'total' => count($contracts),
            'validas' => 0,
            'errores' => 0,
            'omitidas' => 0,
            'importadas' => 0,
            'actualizadas' => 0,
            'advertencias' => 0,
        ];
        $phases = [
            'clientes_agrupados' => [
                'clientes_creados' => 0,
                'clientes_actualizados' => 0,
                'membresias_creadas' => 0,
                'matriculas_creadas' => 0,
                'matriculas_actualizadas' => 0,
                'pagos_historicos' => 0,
                'cuotas_generadas' => 0,
                'advertencias' => 0,
                'errores' => 0,
            ],
        ];

        $seenContracts = [];
        $previewClientCodes = [];
        $previewMembershipKeys = [];
        $warningsByCode = $this->buildSummaryWarnings($contracts, $summaryByCode);

        foreach ($contracts as $row) {
            $errors = $this->validateContractRow($row);
            $warnings = $this->rowWarnings($row);
            $contractKey = $this->contractKey($row);
            if (isset($seenContracts[$contractKey])) {
                $warnings[] = 'Contrato duplicado en el Excel para el mismo CODIGO, membresia y fechas.';
            }
            $seenContracts[$contractKey] = true;

            foreach ($warningsByCode[$row->codigo ?? ''] ?? [] as $warning) {
                $warnings[] = $warning;
            }

            if ($errors !== []) {
                $general['errores']++;
                $phases['clientes_agrupados']['errores']++;
                $rowResults[] = $this->rowResult($row, 'error', $errors, $warnings, null);
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $general['validas']++;
            if ($warnings !== []) {
                $general['advertencias']++;
                $phases['clientes_agrupados']['advertencias']++;
            }

            $existingCliente = $this->findClienteByCodigo((string) $row->codigo, $sucursalId);
            $existingMatricula = $existingCliente
                ? $this->findMatricula($existingCliente->id, (string) $row->membresia, $row->fechaInicio, $row->fechaFin)
                : null;

            if (! $execute) {
                $clientCode = trim((string) $row->codigo);
                if (! isset($previewClientCodes[$clientCode])) {
                    $previewClientCodes[$clientCode] = true;
                    if (! $existingCliente) {
                        $phases['clientes_agrupados']['clientes_creados']++;
                    } else {
                        $phases['clientes_agrupados']['clientes_actualizados']++;
                    }
                }

                $membershipKey = mb_strtolower(trim((string) $row->membresia));
                if ($membershipKey !== '' && ! isset($previewMembershipKeys[$membershipKey])) {
                    $previewMembershipKeys[$membershipKey] = true;
                    if (! $this->resolver->resolverMembresiaPorNombre((string) $row->membresia, $sucursalId)) {
                        $phases['clientes_agrupados']['membresias_creadas']++;
                    }
                }

                $general['importadas']++;
                if ($existingMatricula) {
                    $phases['clientes_agrupados']['matriculas_actualizadas']++;
                } else {
                    $phases['clientes_agrupados']['matriculas_creadas']++;
                }
                $phases['clientes_agrupados']['pagos_historicos']++;
                $phases['clientes_agrupados']['cuotas_generadas'] += $this->shouldCreateInstallments($row) ? (int) $row->cuotasPendientes : 0;
                $rowResults[] = $this->rowResult($row, $warnings !== [] ? 'warning' : 'valid', [], $warnings, $existingMatricula?->id);

                continue;
            }

            try {
                DB::transaction(function () use ($row, $sucursalId, $userId, &$general, &$phases, &$rowResults, $warnings): void {
                    $result = $this->persistContract($row, $sucursalId, $userId);

                    if ($result['cliente_created']) {
                        $general['importadas']++;
                        $phases['clientes_agrupados']['clientes_creados']++;
                    } else {
                        $general['actualizadas']++;
                        $phases['clientes_agrupados']['clientes_actualizados']++;
                    }
                    if ($result['membership_created']) {
                        $phases['clientes_agrupados']['membresias_creadas']++;
                    }
                    if ($result['matricula_created']) {
                        $phases['clientes_agrupados']['matriculas_creadas']++;
                    } else {
                        $phases['clientes_agrupados']['matriculas_actualizadas']++;
                    }
                    $phases['clientes_agrupados']['pagos_historicos']++;
                    $phases['clientes_agrupados']['cuotas_generadas'] += $result['installments_count'];

                    $rowResults[] = $this->rowResult(
                        $row,
                        $warnings !== [] ? 'warning' : 'imported',
                        [],
                        $warnings,
                        $result['matricula_id']
                    );
                });
            } catch (\Throwable $e) {
                $general['errores']++;
                $phases['clientes_agrupados']['errores']++;
                $rowResults[] = $this->rowResult($row, 'error', [$e->getMessage()], $warnings, null);
                if ($stopOnAnyError) {
                    throw $e;
                }
            }
        }

        return [
            'summary' => $general,
            'row_results' => $rowResults,
            'phase_summaries' => $phases,
        ];
    }

    /**
     * @return array{cliente_created: bool, membership_created: bool, matricula_created: bool, installments_count: int, matricula_id: int}
     */
    private function persistContract(ClienteAgrupadoContractRowData $row, int $sucursalId, int $userId): array
    {
        $cliente = $this->findClienteByCodigo((string) $row->codigo, $sucursalId);
        $clienteCreated = false;
        [$nombres, $apellidos] = $this->splitFullName($row->nombreCompleto);

        if (! $cliente) {
            $fallbackUser = $this->sellerUserResolver->fallbackUser();
            $cliente = Cliente::query()->create([
                'codigo' => trim((string) $row->codigo),
                'tipo_documento' => 'CE',
                'numero_documento' => trim((string) $row->codigo),
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $row->celular,
                'estado_cliente' => 'activo',
                'observaciones' => 'Creado por importacion Clientes Agrupados.',
                'created_by' => $fallbackUser->id,
                'updated_by' => $userId,
                'sucursal_id' => $sucursalId,
            ]);
            $clienteCreated = true;
        } else {
            $cliente->update([
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'telefono' => $row->celular ?: $cliente->telefono,
                'estado_cliente' => $cliente->estado_cliente ?: 'activo',
                'updated_by' => $userId,
                'sucursal_id' => $sucursalId,
            ]);
        }

        [$membership, $membershipCreated] = $this->resolveMembership($row, $sucursalId);

        $matricula = $this->findMatricula($cliente->id, $membership->nombre, $row->fechaInicio, $row->fechaFin);
        $matriculaCreated = false;
        $usesInstallments = $this->shouldCreateInstallments($row);
        $asesor = $this->resolveSeller($row->vendedor);

        $matriculaPayload = [
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membership->id,
            'fecha_matricula' => $row->fechaInicio?->toDateString(),
            'fecha_inicio' => $row->fechaInicio?->toDateString(),
            'fecha_fin' => $row->fechaFin?->toDateString(),
            'estado' => $this->resolveMatriculaEstado($row->fechaFin),
            'precio_lista' => (float) $row->precio,
            'descuento_monto' => 0,
            'precio_final' => (float) $row->precio,
            'modalidad_pago' => $usesInstallments ? 'cuotas' : 'contado',
            'requiere_plan_cuotas' => $usesInstallments,
            'cuota_inicial_monto' => (float) ($row->pagado ?? 0),
            'asesor_id' => $asesor?->id,
            'canal_venta' => 'Importacion Clientes Agrupados',
            'sucursal_id' => $sucursalId,
        ];

        if (! $matricula) {
            $matricula = ClienteMatricula::query()->create($matriculaPayload);
            $matriculaCreated = true;
        } else {
            $matricula->update($matriculaPayload);
        }

        $this->upsertHistoricalPayment($cliente, $matricula, $row, $asesor, $sucursalId);
        $installmentsCount = $this->syncInstallments($cliente, $matricula, $row);

        return [
            'cliente_created' => $clienteCreated,
            'membership_created' => $membershipCreated,
            'matricula_created' => $matriculaCreated,
            'installments_count' => $installmentsCount,
            'matricula_id' => $matricula->id,
        ];
    }

    /**
     * @return array{0: Membresia, 1: bool}
     */
    private function resolveMembership(ClienteAgrupadoContractRowData $row, int $sucursalId): array
    {
        $membership = $this->resolver->resolverMembresiaPorNombre((string) $row->membresia, $sucursalId);
        if ($membership) {
            return [$membership, false];
        }

        $duration = 30;
        if ($row->fechaInicio && $row->fechaFin) {
            $duration = max(1, $row->fechaInicio->startOfDay()->diffInDays($row->fechaFin->startOfDay()));
        }

        return [
            Membresia::query()->create([
                'nombre' => mb_substr(trim((string) $row->membresia), 0, 100),
                'descripcion' => 'Creada automaticamente por importacion Clientes Agrupados.',
                'duracion_dias' => $duration,
                'precio_base' => (float) ($row->precio ?? 0),
                'estado' => 'activa',
                'sucursal_id' => $sucursalId,
                'permite_cuotas' => $this->shouldCreateInstallments($row),
                'numero_cuotas_default' => $row->cuotasPendientes ?: null,
                'frecuencia_cuotas_default' => $this->shouldCreateInstallments($row) ? 'mensual' : null,
                'cuota_inicial_monto' => (float) ($row->pagado ?? 0),
                'cuota_inicial_porcentaje' => null,
                'tipo_acceso' => null,
                'permite_congelacion' => false,
            ]),
            true,
        ];
    }

    private function upsertHistoricalPayment(
        Cliente $cliente,
        ClienteMatricula $matricula,
        ClienteAgrupadoContractRowData $row,
        ?User $asesor,
        int $sucursalId
    ): void {
        $paymentMethod = PaymentMethod::withTrashed()
            ->where('nombre', self::HISTORICAL_PAYMENT_METHOD)
            ->where('sucursal_id', $sucursalId)
            ->first();

        if (! $paymentMethod) {
            $paymentMethod = PaymentMethod::query()->create([
                'nombre' => self::HISTORICAL_PAYMENT_METHOD,
                'descripcion' => 'Pagos historicos cargados desde Excel; no afectan caja.',
                'requiere_numero_operacion' => false,
                'requiere_entidad' => false,
                'estado' => 'activo',
                'sucursal_id' => $sucursalId,
            ]);
        } elseif ($paymentMethod->trashed()) {
            $paymentMethod->restore();
        }

        $pago = Pago::query()
            ->where('cliente_matricula_id', $matricula->id)
            ->where('metodo_pago', self::HISTORICAL_PAYMENT_METHOD)
            ->first();

        $payload = [
            'cliente_id' => $cliente->id,
            'cliente_matricula_id' => $matricula->id,
            'monto' => (float) ($row->pagado ?? 0),
            'moneda' => 'PEN',
            'metodo_pago' => self::HISTORICAL_PAYMENT_METHOD,
            'payment_method_id' => $paymentMethod->id,
            'fecha_pago' => ($row->fechaInicio ?? CarbonImmutable::now())->toDateTimeString(),
            'es_pago_parcial' => (float) ($row->deuda ?? 0) > 0,
            'saldo_pendiente' => (float) ($row->deuda ?? 0),
            'registrado_por' => $asesor?->id ?? $this->sellerUserResolver->fallbackUser()->id,
            'sucursal_id' => $sucursalId,
        ];

        if ($pago) {
            $pago->update($payload);
        } else {
            Pago::query()->create($payload);
        }
    }

    private function syncInstallments(Cliente $cliente, ClienteMatricula $matricula, ClienteAgrupadoContractRowData $row): int
    {
        if (! $this->shouldCreateInstallments($row)) {
            EnrollmentInstallment::query()->where('cliente_matricula_id', $matricula->id)->delete();
            $plan = EnrollmentInstallmentPlan::query()->where('cliente_id', $cliente->id)->first();
            if ($plan) {
                $this->installmentService->syncPlanHeaderFromInstallments($plan);
            }

            return 0;
        }

        $plan = EnrollmentInstallmentPlan::query()
            ->where('cliente_id', $cliente->id)
            ->lockForUpdate()
            ->first();

        if (! $plan) {
            $plan = EnrollmentInstallmentPlan::query()->create([
                'cliente_id' => $cliente->id,
                'cliente_matricula_id' => null,
                'monto_total' => 0,
                'numero_cuotas' => 0,
                'monto_cuota' => 0,
                'frecuencia' => 'mensual',
                'fecha_inicio' => $row->fechaInicio?->toDateString() ?? now()->toDateString(),
                'observaciones' => 'Plan creado por importacion Clientes Agrupados.',
            ]);
        }

        EnrollmentInstallment::query()->where('cliente_matricula_id', $matricula->id)->delete();

        $count = (int) $row->cuotasPendientes;
        $amounts = $this->splitAmount((float) $row->deuda, $count);
        $paidOffset = $this->inferPaidInstallmentOffset($row);
        $firstDate = ($row->fechaInicio ?? CarbonImmutable::now())->addMonthsNoOverflow($paidOffset);

        foreach ($amounts as $index => $amount) {
            $dueDate = $firstDate->addMonthsNoOverflow($index)->startOfDay();
            EnrollmentInstallment::query()->create([
                'enrollment_installment_plan_id' => $plan->id,
                'cliente_matricula_id' => $matricula->id,
                'numero_cuota' => $index + 1,
                'monto' => $amount,
                'fecha_vencimiento' => $dueDate->toDateString(),
                'estado' => $dueDate->lt(now()->startOfDay()) ? 'vencida' : 'pendiente',
                'payment_method_id' => null,
                'numero_operacion' => null,
                'pago_id' => null,
                'fecha_pago' => null,
            ]);
        }

        $this->installmentService->syncPlanHeaderFromInstallments($plan->fresh());

        return $count;
    }

    /**
     * @return list<float>
     */
    private function splitAmount(float $amount, int $parts): array
    {
        if ($parts <= 0) {
            return [];
        }

        $totalCents = (int) round($amount * 100);
        $base = intdiv($totalCents, $parts);
        $remainder = $totalCents % $parts;
        $amounts = [];

        for ($i = 0; $i < $parts; $i++) {
            $amounts[] = ($base + ($i < $remainder ? 1 : 0)) / 100;
        }

        return $amounts;
    }

    private function inferPaidInstallmentOffset(ClienteAgrupadoContractRowData $row): int
    {
        $montoCuota = (float) ($row->montoCuota ?? 0);
        if ($montoCuota <= 0) {
            return 0;
        }

        return max(0, (int) floor((float) ($row->pagado ?? 0) / $montoCuota));
    }

    private function shouldCreateInstallments(ClienteAgrupadoContractRowData $row): bool
    {
        return (int) ($row->cuotasPendientes ?? 0) > 0 && (float) ($row->deuda ?? 0) > 0;
    }

    /**
     * @return list<string>
     */
    private function validateContractRow(ClienteAgrupadoContractRowData $row): array
    {
        $errors = [];
        if (! $row->codigo) {
            $errors[] = 'CODIGO obligatorio.';
        }
        if (! $row->nombreCompleto) {
            $errors[] = 'NOMBRES obligatorio.';
        }
        if (! $row->membresia) {
            $errors[] = 'MEMBRESIA obligatoria.';
        }
        if (! $row->fechaInicio || ! $row->fechaFin) {
            $errors[] = 'F. INICIO y F. FIN obligatorias o invalidas.';
        }
        if ($row->precio === null || $row->precio < 0) {
            $errors[] = 'PRECIO invalido.';
        }
        if ($row->pagado === null || $row->pagado < 0) {
            $errors[] = 'PAGADO invalido.';
        }
        if ($row->deuda === null || $row->deuda < 0) {
            $errors[] = 'DEUDA invalida.';
        }
        if ($row->precio !== null && $row->deuda !== null && $row->deuda > $row->precio) {
            $errors[] = 'DEUDA no puede ser mayor que PRECIO.';
        }
        if ((int) ($row->cuotasPendientes ?? 0) > 0 && (float) ($row->deuda ?? 0) > 0 && ($row->montoCuota === null || $row->montoCuota <= 0)) {
            $errors[] = 'MONTO CUOTA obligatorio cuando hay CUOTAS PEND. y DEUDA.';
        }

        return $errors;
    }

    /**
     * @return list<string>
     */
    private function rowWarnings(ClienteAgrupadoContractRowData $row): array
    {
        $warnings = [];
        if ($this->shouldCreateInstallments($row)) {
            $expected = round((float) $row->montoCuota * (int) $row->cuotasPendientes, 2);
            $actual = round((float) $row->deuda, 2);
            if (abs($expected - $actual) > $this->moneyTolerance()) {
                $warnings[] = 'MONTO CUOTA x CUOTAS PEND. difiere de DEUDA.';
            }
        }

        return $warnings;
    }

    /**
     * @param  list<ClienteAgrupadoContractRowData>  $contracts
     * @param  array<string, ClienteAgrupadoSummaryRowData>  $summaryByCode
     * @return array<string, list<string>>
     */
    private function buildSummaryWarnings(array $contracts, array $summaryByCode): array
    {
        $totals = [];
        foreach ($contracts as $row) {
            $code = $row->codigo ?? '';
            if ($code === '') {
                continue;
            }
            $totals[$code] ??= ['precio' => 0.0, 'pagado' => 0.0, 'deuda' => 0.0];
            $totals[$code]['precio'] += (float) ($row->precio ?? 0);
            $totals[$code]['pagado'] += (float) ($row->pagado ?? 0);
            $totals[$code]['deuda'] += (float) ($row->deuda ?? 0);
        }

        $warnings = [];
        foreach ($totals as $code => $total) {
            $summary = $summaryByCode[$code] ?? null;
            if (! $summary) {
                $warnings[$code][] = 'CODIGO no encontrado en Resumen por Cliente.';
                continue;
            }
            if (abs(round($total['precio'], 2) - (float) $summary->precioTotal) > $this->moneyTolerance()) {
                $warnings[$code][] = 'Suma PRECIO del detalle no cuadra con PRECIO TOTAL.';
            }
            if (abs(round($total['pagado'], 2) - (float) $summary->pagado) > $this->moneyTolerance()) {
                $warnings[$code][] = 'Suma PAGADO del detalle no cuadra con PAGADO del resumen.';
            }
            if (abs(round($total['deuda'], 2) - (float) $summary->deudaTotal) > $this->moneyTolerance()) {
                $warnings[$code][] = 'Suma DEUDA del detalle no cuadra con DEUDA TOTAL.';
            }
        }

        return $warnings;
    }

    /**
     * @param  list<ClienteAgrupadoSummaryRowData>  $summaries
     * @return array<string, ClienteAgrupadoSummaryRowData>
     */
    private function summariesByCode(array $summaries): array
    {
        $byCode = [];
        foreach ($summaries as $summary) {
            if ($summary->codigo) {
                $byCode[$summary->codigo] = $summary;
            }
        }

        return $byCode;
    }

    private function findClienteByCodigo(string $codigo, int $sucursalId): ?Cliente
    {
        return Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->where('codigo', trim($codigo))
            ->first();
    }

    private function findMatricula(int $clienteId, string $membresiaNombre, ?CarbonImmutable $fechaInicio, ?CarbonImmutable $fechaFin): ?ClienteMatricula
    {
        $normalized = mb_strtolower(trim($membresiaNombre));

        return ClienteMatricula::query()
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->whereHas('membresia', fn ($q) => $q->whereRaw('LOWER(TRIM(nombre)) = ?', [$normalized]))
            ->when($fechaInicio, fn ($q) => $q->whereDate('fecha_inicio', $fechaInicio->toDateString()))
            ->when($fechaFin, fn ($q) => $q->whereDate('fecha_fin', $fechaFin->toDateString()))
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return array{0:string,1:string}
     */
    private function splitFullName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return ['Sin nombre', 'Sin apellido'];
        }

        $parts = array_map('trim', explode(',', $fullName, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return [mb_substr($parts[0], 0, 100), mb_substr($parts[1], 0, 100)];
        }

        return [mb_substr($fullName, 0, 100), 'Sin apellido'];
    }

    private function resolveSeller(?string $vendedor): ?User
    {
        if ($vendedor === null || trim($vendedor) === '' || mb_strtolower(trim($vendedor)) === 'sin vendedor') {
            return $this->sellerUserResolver->fallbackUser();
        }

        return $this->resolver->resolverUsuarioPorNombreVendedor($vendedor) ?? $this->sellerUserResolver->fallbackUser();
    }

    private function resolveMatriculaEstado(?CarbonImmutable $fechaFin): string
    {
        if (! $fechaFin) {
            return 'activa';
        }

        return $fechaFin->startOfDay()->lt(now()->startOfDay()) ? 'vencida' : 'activa';
    }

    private function contractKey(ClienteAgrupadoContractRowData $row): string
    {
        return implode('|', [
            trim((string) $row->codigo),
            mb_strtolower(trim((string) $row->membresia)),
            $row->fechaInicio?->toDateString() ?? '',
            $row->fechaFin?->toDateString() ?? '',
        ]);
    }

    /**
     * @param  list<string>  $errors
     * @param  list<string>  $warnings
     * @return array<string, mixed>
     */
    private function rowResult(ClienteAgrupadoContractRowData $row, string $estado, array $errors, array $warnings, ?int $modeloId): array
    {
        return [
            'fila' => $row->rowNumber,
            'phase' => 'clientes_agrupados',
            'estado' => $estado,
            'errores' => $errors,
            'warnings' => $warnings,
            'modelo_id' => $modeloId,
            'codigo' => $row->codigo,
            'nombre' => $row->nombreCompleto,
            'paquete' => $row->membresia,
            'vendedor' => $row->vendedor,
            'precio' => $row->precio,
            'pagado' => $row->pagado,
            'deuda' => $row->deuda,
            'info' => $warnings !== [] ? implode('; ', $warnings) : null,
        ];
    }

    private function moneyTolerance(): float
    {
        return (float) config('importacion.money_tolerance', 0.02);
    }
}
