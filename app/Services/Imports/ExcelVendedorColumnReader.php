<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Imports\RawExcelArrayImport;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelVendedorColumnReader
{
    /**
     * Extrae nombres únicos de la columna VENDEDOR (primera aparición define la fila de referencia).
     *
     * @return list<array{fila: int, nombre: string}>
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
        $vendedorCol = $this->findVendedorColumnIndex($headers);
        if ($vendedorCol === null) {
            throw new RuntimeException('No se encontró la columna VENDEDOR en los encabezados.');
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
                $normalized[] = is_string($cell) ? trim($cell) : (string) $cell;
            }
            $joined = implode('|', $normalized);
            if (str_contains($joined, 'VENDEDOR') || str_contains(mb_strtoupper($joined), 'VENDEDOR')) {
                $headers = [];
                foreach ($normalized as $cell) {
                    $headers[] = is_string($cell) ? trim($cell) : '';
                }

                return [$idx, $headers];
            }
        }

        throw new RuntimeException('No se encontró la fila de encabezados con columna VENDEDOR.');
    }

    /**
     * @param  list<string>  $headers
     */
    private function findVendedorColumnIndex(array $headers): ?int
    {
        foreach ($headers as $i => $h) {
            $u = mb_strtoupper(str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', trim($h)), 'UTF-8');
            if ($u === 'VENDEDOR' || str_contains($u, 'VENDEDOR')) {
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
}
