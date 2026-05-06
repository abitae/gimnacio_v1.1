<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Imports\RawExcelArrayImport;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelVendedorColumnReader
{
    public const SHEET_NAME = 'Usuarios Vendedores';

    public const EXPECTED_HEADERS = [
        'VENDEDOR',
        'CLIENTES',
        'ACTIVOS',
        'INACTIVOS',
        'DEUDA_CLIENTES',
        'PRECIO_TOTAL',
        'PAGADO_TOTAL',
        'DEUDA_TOTAL',
        'CUOTAS_REGISTRADAS',
    ];

    /**
     * Extrae nombres únicos de la columna VENDEDOR.
     *
     * @return list<array{fila: int, nombre: string}>
     */
    public function read(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("No se encontro el archivo: {$filePath}");
        }

        $sheet = $this->readSheet($filePath);
        if ($sheet === []) {
            throw new RuntimeException('El archivo no contiene datos.');
        }

        [$headerIndex, $headers] = $this->resolveHeaders($sheet);
        $vendedorCol = $this->findVendedorColumnIndex($headers);
        if ($vendedorCol === null) {
            throw new RuntimeException('No se encontro la columna VENDEDOR en los encabezados.');
        }

        $seen = [];
        $out = [];
        foreach (array_slice($sheet, $headerIndex + 1) as $offset => $row) {
            if (! is_array($row) || $this->isBlankRow($row)) {
                continue;
            }
            $fila = $headerIndex + $offset + 2;
            $raw = $row[$vendedorCol] ?? null;
            $nombre = is_string($raw) ? trim($raw) : trim((string) $raw);
            if ($nombre === '') {
                continue;
            }
            $normalized = SocioActivoRowData::normalizeComparable($nombre);
            if ($normalized === '' || $this->isIgnoredName($normalized)) {
                continue;
            }
            if (isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $out[] = ['fila' => $fila, 'nombre' => $nombre];
        }

        return $out;
    }

    /**
     * @return list<list<mixed>>
     */
    private function readSheet(string $filePath): array
    {
        $sheets = Excel::toArray(new RawExcelArrayImport, $filePath);
        try {
            $sheetNames = Excel::sheetNames($filePath);
        } catch (\Throwable) {
            $sheetNames = [];
        }

        foreach ($sheetNames as $index => $name) {
            if ($this->normalizeHeader($name) === $this->normalizeHeader(self::SHEET_NAME)) {
                return $sheets[$index] ?? [];
            }
        }

        foreach ($sheets as $sheet) {
            try {
                $this->resolveHeaders($sheet);

                return $sheet;
            } catch (\Throwable) {
            }
        }

        return $sheets[0] ?? [];
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
            $headers = [];
            foreach ($row as $cell) {
                $headers[] = is_string($cell) ? trim($cell) : (string) $cell;
            }
            $normalized = array_map(fn (string $header): string => $this->normalizeHeader($header), $headers);
            if (in_array('vendedor', $normalized, true) && in_array('clientes', $normalized, true)) {
                return [$idx, $headers];
            }
        }

        throw new RuntimeException('No se encontro la fila de encabezados con columna VENDEDOR.');
    }

    /**
     * @param  list<string>  $headers
     */
    private function findVendedorColumnIndex(array $headers): ?int
    {
        foreach ($headers as $i => $header) {
            if ($this->normalizeHeader($header) === 'vendedor') {
                return $i;
            }
        }

        return null;
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

    private function isIgnoredName(string $normalized): bool
    {
        return in_array($normalized, ['sin vendedor', 'n/a', '-', '--'], true);
    }

    private function normalizeHeader(string $header): string
    {
        $header = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $header);
        $header = mb_strtoupper(trim($header), 'UTF-8');
        $header = str_replace(['_', '.'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return mb_strtolower(trim($header), 'UTF-8');
    }
}
