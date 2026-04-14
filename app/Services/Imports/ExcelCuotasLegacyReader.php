<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\CuotaClienteRowData;
use App\Imports\RawExcelArrayImport;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelCuotasLegacyReader
{
    public const EXPECTED_HEADERS = [
        'CODIGO',
        'NOMBRES',
        'CELULAR',
        'MEMBRESIA',
        'FECHA INICIO',
        'FECHA FIN',
        'VENDEDOR',
        'PRECIO',
        'PAGO',
        'FECHA CUOTA',
        'DEBE',
        'M. CUOTA',
    ];

    public function __construct(
        private readonly CuotasRowNormalizer $normalizer,
    ) {}

    /**
     * @return list<CuotaClienteRowData>
     */
    public function read(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("No se encontró el archivo: {$filePath}");
        }

        $sheet = Excel::toArray(new RawExcelArrayImport, $filePath)[0] ?? [];
        if ($sheet === []) {
            throw new RuntimeException('El archivo no contiene datos.');
        }

        [$headerIndex, $headers] = $this->resolveHeaders($sheet);
        $rows = [];
        foreach (array_slice($sheet, $headerIndex + 1) as $offset => $row) {
            if (! is_array($row) || $this->isBlankRow($row)) {
                continue;
            }
            $assoc = [];
            foreach ($headers as $index => $header) {
                $assoc[$header] = $row[$index] ?? null;
            }
            $rows[] = $this->normalizer->normalize($headerIndex + $offset + 2, $assoc);
        }

        return $rows;
    }

    /**
     * @param  list<list<mixed>>  $sheet
     * @return array{0: int, 1: list<string>}
     */
    private function resolveHeaders(array $sheet): array
    {
        foreach ($sheet as $idx => $row) {
            if (! is_array($row)) {
                continue;
            }
            $normalized = [];
            foreach ($row as $cell) {
                $normalized[] = is_string($cell) ? trim($cell) : $cell;
            }
            $joined = implode('|', array_map(fn ($c) => (string) $c, $normalized));
            if (str_contains($joined, 'CODIGO') && str_contains($joined, 'FECHA CUOTA')) {
                $headers = [];
                foreach ($normalized as $cell) {
                    $headers[] = is_string($cell) ? trim($cell) : '';
                }

                return [$idx, $headers];
            }
        }

        throw new RuntimeException('No se encontró la fila de encabezados esperada (CODIGO, FECHA CUOTA, …).');
    }

    /**
     * @param  list<mixed>  $row
     */
    private function isBlankRow(array $row): bool
    {
        foreach ($row as $cell) {
            if ($cell !== null && trim((string) $cell) !== '') {
                return false;
            }
        }

        return true;
    }
}
