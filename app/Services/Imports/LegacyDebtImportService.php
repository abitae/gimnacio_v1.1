<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use App\Models\Core\ClientDebt;
use Illuminate\Support\Facades\DB;

class LegacyDebtImportService
{
    public function __construct(
        private readonly ImportRelationResolverService $resolver,
    ) {}

    /**
     * @param  list<DeudaClienteRowData>  $rows
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    public function process(
        array $rows,
        int $sucursalId,
        int $userId,
        bool $execute,
        bool $stopOnAnyError = false
    ): array {
        $rowResults = [];
        $summary = [
            'total' => count($rows),
            'validas' => 0,
            'errores' => 0,
            'importadas' => 0,
            'omitidas' => 0,
        ];

        foreach ($rows as $row) {
            if ($row->isPersonalizado()) {
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'skipped', 'errores' => ['TIPO PLAN personalizado omitido.'], 'modelo_id' => null];

                continue;
            }

            if (! $row->shouldProcessDebt()) {
                $summary['omitidas']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'skipped', 'errores' => ['Sin deuda (DEBE vacío o cero).'], 'modelo_id' => null];

                continue;
            }

            $errors = $this->validateDebtRow($row);
            if ($errors !== []) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => $errors, 'modelo_id' => null];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $cliente = $this->resolver->resolverClientePorCodigoODocumento($row->codigo, $row->dni, $sucursalId, 'DNI');
            if (! $cliente) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => ['Cliente no encontrado por CODIGO/DNI.'], 'modelo_id' => null];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $montoPagado = round((float) $row->costo - (float) $row->debe, 2);
            if ($montoPagado < 0) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => ['monto_pagado calculado negativo.'], 'modelo_id' => null];
                if ($stopOnAnyError) {
                    break;
                }

                continue;
            }

            $cuotificada = $this->findCuotificadaForResumen($cliente->id, $sucursalId, $row);

            $summary['validas']++;

            if (! $execute) {
                if (! $cuotificada) {
                    $summary['importadas']++;
                } else {
                    $summary['omitidas']++;
                }
                $info = null;
                if ($cuotificada) {
                    $tol = (float) config('importacion.money_tolerance', 0.02);
                    $diff = abs((float) $cuotificada->monto_total - (float) $row->costo);
                    $info = $diff > $tol
                        ? 'Ya existe deuda cuotificada; COSTO del resumen difiere del importado por cuotas.'
                        : 'Ya existe deuda cuotificada para este plan; no se creará deuda general al confirmar.';
                }
                $rowResults[] = [
                    'fila' => $row->rowNumber,
                    'estado' => 'valid',
                    'errores' => [],
                    'modelo_id' => null,
                    'info' => $info,
                ];

                continue;
            }

            try {
                DB::transaction(function () use ($row, $cliente, $sucursalId, $montoPagado, $cuotificada, &$summary, &$rowResults): void {
                    if ($cuotificada) {
                        $tol = (float) config('importacion.money_tolerance', 0.02);
                        $diff = abs((float) $cuotificada->monto_total - (float) $row->costo);
                        $obs = $cuotificada->observaciones ?? '';
                        if ($diff > $tol) {
                            $msg = 'Import resumen: inconsistencia COSTO vs deuda cuotificada (diff '.round($diff, 2).').';
                            $cuotificada->update([
                                'observaciones' => $obs !== '' ? $obs.' | '.$msg : $msg,
                            ]);
                        }
                        $summary['omitidas']++;
                        $rowResults[] = [
                            'fila' => $row->rowNumber,
                            'estado' => 'skipped',
                            'errores' => [],
                            'modelo_id' => $cuotificada->id,
                            'info' => 'Omitido: ya existe deuda cuotificada para este cliente/plan.',
                        ];

                        return;
                    }

                    $saldo = (float) $row->debe;
                    $estado = $saldo <= 0 ? 'pagado' : ($montoPagado > 0 ? 'parcial' : 'pendiente');

                    $debt = ClientDebt::create([
                        'cliente_id' => $cliente->id,
                        'sucursal_id' => $sucursalId,
                        'venta_id' => null,
                        'origen_tipo' => $this->mapOrigenTipo($row->tipoPlan),
                        'origen_id' => null,
                        'tipo_deuda' => 'general',
                        'referencia' => $row->plan ? mb_substr(trim((string) $row->plan), 0, 255) : null,
                        'plan_fecha_inicio' => $row->fechaInicio?->toDateString(),
                        'plan_fecha_fin' => $row->fechaFin?->toDateString(),
                        'monto_total' => (float) $row->costo,
                        'monto_pagado' => $montoPagado,
                        'saldo_pendiente' => $saldo,
                        'fecha_registro' => $row->fechaInicio?->toDateString() ?? now()->toDateString(),
                        'fecha_vencimiento' => $row->fechaFin?->toDateString(),
                        'estado' => $estado,
                        'observaciones' => $this->buildObservaciones($row),
                    ]);
                    $summary['importadas']++;
                    $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'imported', 'errores' => [], 'modelo_id' => $debt->id];
                });
            } catch (\Throwable $e) {
                $summary['errores']++;
                $rowResults[] = ['fila' => $row->rowNumber, 'estado' => 'error', 'errores' => [$e->getMessage()], 'modelo_id' => null];
                if ($stopOnAnyError) {
                    throw $e;
                }
            }
        }

        return ['summary' => $summary, 'row_results' => $rowResults];
    }

    /**
     * @return list<string>
     */
    private function validateDebtRow(DeudaClienteRowData $row): array
    {
        $errors = [];
        if ($row->debe === null || $row->debe < 0) {
            $errors[] = 'DEBE inválido.';
        }
        if ($row->costo === null || $row->costo < 0) {
            $errors[] = 'COSTO inválido.';
        }
        if ($row->debe > $row->costo) {
            $errors[] = 'DEBE no puede ser mayor que COSTO.';
        }
        if (! $row->dni || ! $row->plan) {
            $errors[] = 'DNI y PLAN obligatorios.';
        }

        return $errors;
    }

    private function mapOrigenTipo(?string $tipoPlan): string
    {
        $t = DeudaClienteRowData::normalizeComparable($tipoPlan);

        return match (true) {
            str_contains($t, 'membres') => 'MEMBRESIA',
            str_contains($t, 'matric') => 'MATRICULA',
            str_contains($t, 'alquiler') => 'ALQUILER',
            str_contains($t, 'pos') => 'POS',
            default => 'OTRO',
        };
    }

    private function buildObservaciones(DeudaClienteRowData $row): string
    {
        $parts = array_filter([
            'Plan: '.(string) $row->plan,
            $row->vendedor ? 'Vendedor: '.$row->vendedor : null,
        ]);

        return implode(' | ', $parts);
    }

    private function findCuotificadaForResumen(int $clienteId, int $sucursalId, DeudaClienteRowData $row): ?ClientDebt
    {
        $planRef = trim((string) ($row->plan ?? ''));
        if ($planRef === '') {
            return null;
        }

        $q = ClientDebt::query()
            ->where('cliente_id', $clienteId)
            ->where('sucursal_id', $sucursalId)
            ->where('tipo_deuda', 'cuotificada')
            ->whereRaw('LOWER(TRIM(COALESCE(referencia, ""))) = ?', [mb_strtolower($planRef)]);

        if ($row->fechaInicio) {
            $q->whereDate('plan_fecha_inicio', $row->fechaInicio->toDateString());
        }
        if ($row->fechaFin) {
            $q->whereDate('plan_fecha_fin', $row->fechaFin->toDateString());
        }

        return $q->first();
    }
}
