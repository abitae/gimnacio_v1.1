<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\CuotaClienteRowData;
use App\Imports\RawExcelArrayImport;
use Illuminate\Support\Facades\File;
use Maatwebsite\Excel\Facades\Excel;
use RuntimeException;

class ExcelCuotasLegacyReader
{
    public const SHEET_NAME = 'Detalle Cuotas';

    public const EXPECTED_HEADERS = [
        'CODIGO',
        'CLIENTE',
        'CELULAR',
        'MEMBRESIA',
        'FECHA_INICIO',
        'FECHA_FIN',
        'VENDEDOR',
        'PRECIO',
        'PAGO',
        'FECHA_CUOTA',
        'DEBE',
        'M_CUOTA',
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
            throw new RuntimeException("No se encontro el archivo: {$filePath}");
        }

        $sheet = $this->readSheet($filePath);
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
            if (in_array('codigo', $normalized, true) && in_array('fecha cuota', $normalized, true) && (in_array('m cuota', $normalized, true) || in_array('monto cuota', $normalized, true))) {
                return [$idx, $headers];
            }
        }

        throw new RuntimeException('No se encontro la fila de encabezados esperada para Detalle Cuotas.');
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

    private function normalizeHeader(string $header): string
    {
        $header = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $header);
        $header = mb_strtoupper(trim($header), 'UTF-8');
        $header = str_replace(['_', '.'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return mb_strtolower(trim($header), 'UTF-8');
    }
}
