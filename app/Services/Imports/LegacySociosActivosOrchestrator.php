<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;

class LegacySociosActivosOrchestrator
{
    public function __construct(
        private readonly ExcelSociosReader $reader,
        private readonly UserImportService $userImport,
        private readonly LegacyClientImportService $clientImport,
        private readonly LegacyMembershipImportService $membershipImport,
    ) {}

    /**
     * @param  array{duplicate_mode?: string, stop_on_error?: bool}  $options
     * @return array{summary: array<string, int>, phase_summaries: array<string, array<string, int>>, row_results: list<array<string, mixed>>}
     */
    public function process(
        string $path,
        int $sucursalId,
        int $userId,
        bool $execute,
        array $options = []
    ): array {
        $rows = $this->reader->read($path);
        $duplicateMode = (string) ($options['duplicate_mode'] ?? 'crear_o_actualizar');
        $stopOnAnyError = (bool) ($options['stop_on_error'] ?? false);

        $membershipRows = array_values(array_filter(
            $rows,
            fn (SocioActivoRowData $row) => $row->soportaImportacionMembresia()
        ));

        $users = $this->userImport->process(
            $this->collectUserEntries($membershipRows),
            $sucursalId,
            $userId,
            $execute,
            $stopOnAnyError
        );

        $clients = $this->clientImport->process(
            $membershipRows,
            $sucursalId,
            $userId,
            $execute,
            $duplicateMode,
            $stopOnAnyError
        );

        $memberships = $this->membershipImport->process(
            $rows,
            $sucursalId,
            $userId,
            $execute,
            $stopOnAnyError
        );

        $phaseSummaries = [
            'usuarios' => $users['summary'],
            'clientes' => $clients['summary'],
            'membresias' => $memberships['summary'],
        ];

        $rowResults = array_merge(
            $users['row_results'],
            $clients['row_results'],
            $memberships['row_results'],
        );

        return [
            'summary' => $this->buildGlobalSummary($rows, $phaseSummaries, $rowResults),
            'phase_summaries' => $phaseSummaries,
            'row_results' => $rowResults,
        ];
    }

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @return list<array{fila:int, nombre:string}>
     */
    private function collectUserEntries(array $rows): array
    {
        $seen = [];
        $entries = [];

        foreach ($rows as $row) {
            foreach ([$row->vendedor, $row->repartido] as $candidate) {
                $displayName = trim((string) $candidate);
                $normalized = SocioActivoRowData::normalizeComparable($displayName);
                if ($normalized === '' || isset($seen[$normalized])) {
                    continue;
                }

                $seen[$normalized] = true;
                $entries[] = [
                    'fila' => $row->rowNumber,
                    'nombre' => $displayName,
                ];
            }
        }

        return $entries;
    }

    /**
     * @param  list<SocioActivoRowData>  $rows
     * @param  array<string, array<string, int>>  $phaseSummaries
     * @param  list<array<string, mixed>>  $rowResults
     * @return array<string, int>
     */
    private function buildGlobalSummary(array $rows, array $phaseSummaries, array $rowResults): array
    {
        $errorRows = [];
        $skippedRows = [];

        foreach ($rowResults as $row) {
            $fila = (int) ($row['fila'] ?? 0);
            if ($fila <= 0) {
                continue;
            }

            if (($row['estado'] ?? null) === 'error') {
                $errorRows[$fila] = true;
            }
            if (($row['estado'] ?? null) === 'skipped') {
                $skippedRows[$fila] = true;
            }
        }

        return [
            'total' => count($rows),
            'validas' => max(0, count($rows) - count($errorRows)),
            'errores' => count($errorRows),
            'omitidas' => count($skippedRows),
            'importadas' => (int) ($phaseSummaries['clientes']['importadas'] ?? 0)
                + (int) ($phaseSummaries['membresias']['importadas'] ?? 0)
                + (int) ($phaseSummaries['usuarios']['a_crear'] ?? 0),
            'actualizadas' => (int) ($phaseSummaries['clientes']['actualizadas'] ?? 0),
        ];
    }
}
