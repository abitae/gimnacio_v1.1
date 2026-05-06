<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use App\Imports\RawExcelArrayImport;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use RuntimeException;

class ExcelContratosDeudaReader
{
    public const SHEET_NAME = 'Contratos Deuda';

    public const EXPECTED_HEADERS = [
        'CODIGO',
        'NOMBRES',
        'CELULAR',
        'MEMBRESIA',
        'VENDEDOR',
        'F. INICIO',
        'F. FIN',
        'PRECIO',
        'PAGADO',
        'DEUDA',
        'MONTO CUOTA',
        'CUOTAS PEND.',
        'ESTADO',
    ];

    /**
     * @return list<SocioActivoRowData>
     */
    public function read(string $filePath): array
    {
        if (! File::exists($filePath)) {
            throw new RuntimeException("No se encontro el archivo Excel: {$filePath}");
        }

        $sheet = $this->readSheet($filePath);
        if ($sheet === []) {
            throw new RuntimeException('El archivo Excel no contiene datos para contratos.');
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

            $rows[] = $this->normalize($headerIndex + $offset + 2, $assoc);
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
     * @return array{0:int,1:list<string>}
     */
    private function resolveHeaders(array $sheet): array
    {
        foreach ($sheet as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $headers = [];
            foreach ($row as $cell) {
                $headers[] = is_string($cell) ? trim($cell) : (string) $cell;
            }

            $normalized = array_map(fn (string $header): string => $this->normalizeHeader($header), $headers);
            if (in_array('codigo', $normalized, true) && in_array('membresia', $normalized, true) && in_array('f inicio', $normalized, true) && in_array('f fin', $normalized, true)) {
                return [$index, $headers];
            }
        }

        throw new RuntimeException('No se encontro la fila de encabezados esperada para Contratos Deuda.');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalize(int $rowNumber, array $row): SocioActivoRowData
    {
        [$nombres, $apellidos] = $this->splitFullName($this->cleanString($this->pick($row, ['NOMBRES'])));

        return new SocioActivoRowData(
            rowNumber: $rowNumber,
            codigo: $this->cleanString($this->pick($row, ['CODIGO', 'CÓDIGO'])),
            nombres: $nombres,
            apellidos: $apellidos,
            correo: null,
            dni: null,
            edad: null,
            celular: $this->cleanPhone($this->pick($row, ['CELULAR'])),
            fechaNacimiento: null,
            direccion: null,
            tipoVenta: 'membresia',
            origen: 'Importacion Contratos Deuda',
            paquete: $this->cleanString($this->pick($row, ['MEMBRESIA', 'MEMBRESÍA'])),
            fechaInscripcion: $this->parseDate($this->pick($row, ['F. INICIO', 'FECHA INICIO'])),
            costo: $this->parseMoney($this->pick($row, ['PRECIO'])),
            fechaInicio: $this->parseDate($this->pick($row, ['F. INICIO', 'FECHA INICIO'])),
            fechaFin: $this->parseDate($this->pick($row, ['F. FIN', 'FECHA FIN'])),
            vendedor: $this->cleanString($this->pick($row, ['VENDEDOR'])),
            repartido: null,
            sesiones: null,
            asistencias: null,
            reservas: null,
            genero: null,
            estado: $this->cleanString($this->pick($row, ['ESTADO'])),
            estadoFinal: null,
            fechaCreacion: null,
            precioTotal: $this->parseMoney($this->pick($row, ['PRECIO'])),
            pagadoTotal: $this->parseMoney($this->pick($row, ['PAGADO'])),
            deudaTotal: $this->parseMoney($this->pick($row, ['DEUDA'])),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $candidates
     */
    private function pick(array $row, array $candidates): mixed
    {
        $indexed = [];
        foreach ($row as $header => $value) {
            $indexed[$this->normalizeHeader((string) $header)] = $value;
        }

        foreach ($candidates as $candidate) {
            if (array_key_exists($candidate, $row) && $row[$candidate] !== null && $row[$candidate] !== '') {
                return $row[$candidate];
            }

            $key = $this->normalizeHeader($candidate);
            if (array_key_exists($key, $indexed) && $indexed[$key] !== null && $indexed[$key] !== '') {
                return $indexed[$key];
            }
        }

        return null;
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function splitFullName(?string $fullName): array
    {
        $fullName = trim((string) $fullName);
        if ($fullName === '') {
            return [null, null];
        }

        $parts = array_map('trim', explode(',', $fullName, 2));
        if (count($parts) === 2 && $parts[0] !== '' && $parts[1] !== '') {
            return [mb_substr($parts[0], 0, 100), mb_substr($parts[1], 0, 100)];
        }

        $tokens = preg_split('/\s+/u', $fullName) ?: [];
        if (count($tokens) <= 2) {
            return [$tokens[0] ?? null, implode(' ', array_slice($tokens, 1)) ?: 'Sin apellido'];
        }

        return [
            implode(' ', array_slice($tokens, 0, count($tokens) - 2)),
            implode(' ', array_slice($tokens, -2)),
        ];
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

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $value = floor((float) $value) === (float) $value
                ? (string) (int) $value
                : rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    private function cleanPhone(mixed $value): ?string
    {
        $phone = $this->cleanString($value);

        return $phone !== null ? preg_replace('/\s+/', '', $phone) : null;
    }

    private function parseMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value) || is_numeric($value)) {
            return round((float) $value, 2);
        }

        $normalized = str_ireplace(['s/', 'pen'], '', trim((string) $value));
        $normalized = str_replace([' ', ','], ['', '.'], $normalized);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || $normalized === '-' || ! is_numeric($normalized)) {
            return null;
        }

        return round((float) $normalized, 2);
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            try {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            } catch (\Throwable) {
            }
        }

        $value = $this->cleanString($value);
        if ($value === null) {
            return null;
        }

        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'Y/m/d'] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
            }
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $header);
        $header = Str::ascii($header);
        $header = mb_strtoupper(trim($header), 'UTF-8');
        $header = str_replace(['_', '.'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return mb_strtolower(trim($header), 'UTF-8');
    }
}
