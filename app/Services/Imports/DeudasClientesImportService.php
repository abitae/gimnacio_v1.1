<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use App\Models\Core\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class DeudasClientesImportService
{
    public function __construct(
        private readonly ExcelDeudasReader $reader,
        private readonly MatriculaDebtMatcher $matcher,
        private readonly MatriculaDebtReconciler $reconciler,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(string $filePath, bool $execute = false): array
    {
        $rows = $this->reader->read($filePath);
        $matchedCandidates = [];

        $report = [
            'phase' => 'deudas-clientes',
            'dry_run' => ! $execute,
            'processed_rows' => count($rows),
            'matched_exact' => 0,
            'matched_flexible' => 0,
            'updated_matriculas' => 0,
            'updated_pagos' => 0,
            'no_cliente' => 0,
            'sin_matricula' => 0,
            'ambiguos' => 0,
            'personalizados' => 0,
            'invalidos' => 0,
            'skipped_without_debt' => 0,
            'already_synced' => 0,
            'duplicados_misma_matricula' => 0,
            'warnings' => [],
        ];

        $reportRows = [
            'no_cliente' => [],
            'sin_matricula' => [],
            'ambiguos' => [],
            'invalidos' => [],
            'personalizados' => [],
        ];

        foreach ($rows as $row) {
            if ($row->isPersonalizado()) {
                $report['personalizados']++;
                $reportRows['personalizados'][] = $this->reportRow($row, 'TIPO PLAN PERSONALIZADO.');
                continue;
            }

            if ($row->debe === null || $row->debe <= 0) {
                $report['skipped_without_debt']++;
                continue;
            }

            if (! $row->dni || ! $row->plan || ! $row->fechaInicio || ! $row->fechaFin || $row->costo === null) {
                $report['invalidos']++;
                $reportRows['invalidos'][] = $this->reportRow($row, 'Fila incompleta para conciliacion.');
                continue;
            }

            if ($row->debe > $row->costo) {
                $report['invalidos']++;
                $reportRows['invalidos'][] = $this->reportRow($row, 'DEBE no puede ser mayor que COSTO.');
                continue;
            }

            $cliente = Cliente::query()
                ->where('tipo_documento', 'DNI')
                ->where('numero_documento', $row->dni)
                ->first();

            if (! $cliente) {
                $report['no_cliente']++;
                $reportRows['no_cliente'][] = $this->reportRow($row, 'No existe cliente con ese DNI.');
                continue;
            }

            $match = $this->matcher->findFor($cliente, $row);
            if ($match['status'] === 'not_found') {
                $report['sin_matricula']++;
                $reportRows['sin_matricula'][] = $this->reportRow($row, $match['warnings'][0] ?? 'Sin matricula compatible.');
                continue;
            }

            if ($match['status'] === 'ambiguous') {
                $report['ambiguos']++;
                $reportRows['ambiguos'][] = $this->reportRow($row, $match['warnings'][0] ?? 'Match ambiguo.');
                continue;
            }

            $matchedCandidates[$match['matricula']->id][] = [
                'row' => $row,
                'status' => $match['status'],
                'matricula' => $match['matricula'],
            ];
        }

        foreach ($matchedCandidates as $matriculaId => $candidates) {
            usort($candidates, function (array $left, array $right): int {
                $debtComparison = ($left['row']->debe <=> $right['row']->debe);
                if ($debtComparison !== 0) {
                    return $debtComparison;
                }

                return $right['row']->rowNumber <=> $left['row']->rowNumber;
            });

            $selected = $candidates[0];
            $report[$selected['status']]++;

            if (count($candidates) > 1) {
                $report['duplicados_misma_matricula'] += count($candidates) - 1;
                $report['warnings'][] = [
                    'row' => $selected['row']->rowNumber,
                    'dni' => $selected['row']->dni,
                    'plan' => $selected['row']->plan,
                    'warning' => sprintf(
                        'Se detectaron %d filas para la misma matricula %d. Se conserva la menor deuda (%s).',
                        count($candidates),
                        $matriculaId,
                        number_format((float) $selected['row']->debe, 2, '.', '')
                    ),
                ];
            }

            $result = $execute
                ? DB::transaction(fn () => $this->reconciler->reconcile($selected['matricula'], $selected['row'], true))
                : $this->reconciler->reconcile($selected['matricula'], $selected['row'], false);

            if ($result['status'] === 'already_synced') {
                $report['already_synced']++;
            }

            if ($result['updated_matricula']) {
                $report['updated_matriculas']++;
            }

            if ($result['updated_pago']) {
                $report['updated_pagos']++;
            }

            if ($result['warning']) {
                $report['warnings'][] = [
                    'row' => $row->rowNumber,
                    'dni' => $row->dni,
                    'plan' => $row->plan,
                    'warning' => $result['warning'],
                ];
            }
        }

        $report['report_paths'] = $this->writeReports($reportRows);

        return $report;
    }

    /**
     * @param  array<string, array<int, array<int, mixed>>>  $reportRows
     * @return array<string, string>
     */
    private function writeReports(array $reportRows): array
    {
        $directory = storage_path('app/imports');
        File::ensureDirectoryExists($directory);

        $paths = [];
        foreach ($reportRows as $type => $rows) {
            $path = $directory."/deudas_{$type}.csv";
            $handle = fopen($path, 'w');
            fputcsv($handle, ['row', 'dni', 'nombre', 'plan', 'fecha_inicio', 'fecha_fin', 'costo', 'debe', 'vendedor', 'reason']);
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
            $paths[$type] = $path;
        }

        return $paths;
    }

    /**
     * @return array<int, mixed>
     */
    private function reportRow(DeudaClienteRowData $row, string $reason): array
    {
        return [
            $row->rowNumber,
            $row->dni,
            $row->nombreRaw,
            $row->plan,
            $row->fechaInicio?->toDateString(),
            $row->fechaFin?->toDateString(),
            $row->costo,
            $row->debe,
            $row->vendedor,
            $reason,
        ];
    }
}
