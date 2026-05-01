<?php

namespace App\Services\Imports;

use App\Imports\RawExcelArrayImport;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelClientesAgrupadosReader
{
    public function __construct(
        private readonly ClientesAgrupadosRowNormalizer $normalizer,
    ) {}

    /**
     * @return array{contracts: list<\App\DataTransferObjects\Imports\ClienteAgrupadoContractRowData>, summaries: list<\App\DataTransferObjects\Imports\ClienteAgrupadoSummaryRowData>}
     */
    public function read(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("No se encontro el archivo Excel: {$filePath}");
        }

        $sheets = Excel::toArray(new RawExcelArrayImport, $filePath);
        if ($sheets === []) {
            throw new RuntimeException('El archivo Excel no contiene hojas legibles.');
        }

        $contractsSheet = $this->findSheetByTitleOrHeaders($sheets, 'Detalle por Contrato', ['CODIGO', 'MEMBRESIA', 'PRECIO', 'DEUDA']);
        $summarySheet = $this->findSheetByTitleOrHeaders($sheets, 'Resumen por Cliente', ['CODIGO', 'PRECIO TOTAL', 'DEUDA TOTAL']);

        if ($contractsSheet === null) {
            throw new RuntimeException('No se encontro la hoja "Detalle por Contrato" o sus encabezados esperados.');
        }
        if ($summarySheet === null) {
            throw new RuntimeException('No se encontro la hoja "Resumen por Cliente" o sus encabezados esperados.');
        }

        return [
            'contracts' => $this->readContracts($contractsSheet),
            'summaries' => $this->readSummaries($summarySheet),
        ];
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @return list<\App\DataTransferObjects\Imports\ClienteAgrupadoContractRowData>
     */
    private function readContracts(array $sheet): array
    {
        [$headerIndex, $headers] = $this->resolveHeaders($sheet, ['CODIGO', 'MEMBRESIA', 'PRECIO', 'DEUDA']);
        $rows = [];
        foreach (array_slice($sheet, $headerIndex + 1) as $offset => $row) {
            if (! is_array($row) || $this->isBlankRow($row)) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = $row[$index] ?? null;
            }
            $rows[] = $this->normalizer->normalizeContract($headerIndex + $offset + 2, $assoc);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @return list<\App\DataTransferObjects\Imports\ClienteAgrupadoSummaryRowData>
     */
    private function readSummaries(array $sheet): array
    {
        [$headerIndex, $headers] = $this->resolveHeaders($sheet, ['CODIGO', 'PRECIO TOTAL', 'DEUDA TOTAL']);
        $rows = [];
        foreach (array_slice($sheet, $headerIndex + 1) as $offset => $row) {
            if (! is_array($row) || $this->isBlankRow($row)) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = $row[$index] ?? null;
            }
            $rows[] = $this->normalizer->normalizeSummary($headerIndex + $offset + 2, $assoc);
        }

        return $rows;
    }

    /**
     * @param  array<int, array<int, array<int, mixed>>>  $sheets
     * @param  list<string>  $requiredHeaders
     * @return array<int, array<int, mixed>>|null
     */
    private function findSheetByTitleOrHeaders(array $sheets, string $title, array $requiredHeaders): ?array
    {
        $titleKey = $this->normalizeHeaderKey($title);

        foreach ($sheets as $sheet) {
            $firstCell = isset($sheet[0][0]) ? $this->normalizeHeaderKey((string) $sheet[0][0]) : '';
            if (str_contains($firstCell, $titleKey)) {
                return $sheet;
            }
        }

        foreach ($sheets as $sheet) {
            try {
                $this->resolveHeaders($sheet, $requiredHeaders);

                return $sheet;
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @param  list<string>  $requiredHeaders
     * @return array{0:int,1:array<int,string>}
     */
    private function resolveHeaders(array $sheet, array $requiredHeaders): array
    {
        $required = array_map(fn (string $h): string => $this->normalizeHeaderKey($h), $requiredHeaders);

        foreach ($sheet as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = array_map(fn (mixed $cell): string => $this->normalizeHeaderKey((string) $cell), $row);
            $hasAll = true;
            foreach ($required as $header) {
                if (! in_array($header, $normalized, true)) {
                    $hasAll = false;
                    break;
                }
            }

            if (! $hasAll) {
                continue;
            }

            $headers = [];
            foreach ($row as $cell) {
                $headers[] = trim((string) $cell);
            }

            return [$index, $headers];
        }

        throw new RuntimeException('No se encontro la fila de encabezados esperada.');
    }

    /**
     * @param  array<int, mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeaderKey(string $header): string
    {
        $header = Str::ascii($header);
        $header = mb_strtoupper(trim($header), 'UTF-8');
        $header = str_replace(['_', '.'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return trim($header);
    }
}
