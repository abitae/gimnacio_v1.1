<?php

namespace App\Services\Imports;

class LegacyInstallmentImportService
{
    public function __construct(
        private readonly GroupedInstallmentImportService $groupedInstallmentImport,
    ) {}

    /**
     * @param  list<\App\DataTransferObjects\Imports\CuotaClienteRowData>  $rows
     * @return array{summary: array<string, int>, row_results: list<array<string, mixed>>}
     */
    public function process(
        array $rows,
        int $sucursalId,
        int $userId,
        bool $execute,
        bool $stopOnAnyError = false
    ): array {
        return $this->groupedInstallmentImport->process($rows, $sucursalId, $userId, $execute, $stopOnAnyError);
    }
}
