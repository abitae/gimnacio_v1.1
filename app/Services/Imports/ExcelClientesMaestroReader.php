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

class ExcelClientesMaestroReader
{
    public const SHEET_NAME = 'Clientes Maestro';

    public const EXPECTED_HEADERS = [
        'CODIGO',
        'CLIENTE',
        'DNI',
        'CELULAR',
        'CORREO',
        'GENERO',
        'EDAD',
        'ORIGEN',
        'ULTIMA_MEMBRESIA',
        'COSTO',
        'FECHA_INICIO',
        'FECHA_FIN',
        'ESTADO',
        'ESTADO_FINAL',
        'EN_REPORTE_ACTIVOS',
        'VENDEDOR',
        'FECHA_CREACION',
        'PRECIO_TOTAL',
        'PAGADO_TOTAL',
        'DEUDA_TOTAL',
        'DEUDA_DETALLE',
        'CONTRATOS_DEUDA',
        'PLANES_CON_DEUDA',
        'CUOTAS_REGISTRADAS',
        'MONTO_CUOTAS',
        'PRIMERA_CUOTA',
        'ULTIMA_CUOTA',
        'TIENE_DEUDA',
    ];

    /**
     * @return list<SocioActivoRowData>
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

        try {
            $sheetNames = Excel::sheetNames($filePath);
        } catch (\Throwable) {
            $sheetNames = [];
        }
        $sheet = $this->findSheet($sheets, $sheetNames);

        if ($sheet === null) {
            throw new RuntimeException('No se encontro la hoja "Clientes Maestro" o sus encabezados esperados.');
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
     * @param  array<int, array<int, array<int, mixed>>>  $sheets
     * @param  list<string>  $sheetNames
     * @return array<int, array<int, mixed>>|null
     */
    private function findSheet(array $sheets, array $sheetNames): ?array
    {
        foreach ($sheetNames as $index => $name) {
            if ($this->normalizeHeader($name) === $this->normalizeHeader(self::SHEET_NAME)) {
                return $sheets[$index] ?? null;
            }
        }

        foreach ($sheets as $sheet) {
            try {
                $this->resolveHeaders($sheet);

                return $sheet;
            } catch (\Throwable) {
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<int, mixed>>  $sheet
     * @return array{0:int,1:array<int,string>}
     */
    private function resolveHeaders(array $sheet): array
    {
        $expected = array_map(fn (string $header): string => $this->normalizeHeader($header), self::EXPECTED_HEADERS);

        foreach ($sheet as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $normalized = array_map(fn (mixed $cell): string => $this->normalizeHeader((string) $cell), $row);
            if (count(array_intersect($expected, $normalized)) < 8) {
                continue;
            }

            $headers = [];
            foreach ($row as $cell) {
                $headers[] = trim((string) $cell);
            }

            return [$index, $headers];
        }

        throw new RuntimeException('No se encontro la fila de encabezados esperada para Clientes Maestro.');
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function normalize(int $rowNumber, array $row): SocioActivoRowData
    {
        [$nombres, $apellidos] = $this->splitFullName($this->normalizeScalar($row['CLIENTE'] ?? null));

        return new SocioActivoRowData(
            rowNumber: $rowNumber,
            codigo: $this->normalizeScalar($row['CODIGO'] ?? null),
            nombres: $nombres,
            apellidos: $apellidos,
            correo: $this->normalizeEmail($row['CORREO'] ?? null),
            dni: $this->normalizeDocument($row['DNI'] ?? null),
            edad: $this->normalizeScalar($row['EDAD'] ?? null),
            celular: $this->normalizePhone($row['CELULAR'] ?? null),
            fechaNacimiento: null,
            direccion: null,
            tipoVenta: 'membresia',
            origen: $this->normalizeScalar($row['ORIGEN'] ?? null),
            paquete: $this->normalizeScalar($row['ULTIMA_MEMBRESIA'] ?? null),
            fechaInscripcion: $this->parseDate($row['FECHA_CREACION'] ?? null),
            costo: $this->parseMoney($row['COSTO'] ?? null),
            fechaInicio: $this->parseDate($row['FECHA_INICIO'] ?? null),
            fechaFin: $this->parseDate($row['FECHA_FIN'] ?? null),
            vendedor: $this->normalizeScalar($row['VENDEDOR'] ?? null),
            repartido: null,
            sesiones: null,
            asistencias: null,
            reservas: null,
            genero: $this->normalizeScalar($row['GENERO'] ?? null),
            estado: $this->normalizeScalar($row['ESTADO'] ?? null),
            estadoFinal: $this->normalizeScalar($row['ESTADO_FINAL'] ?? null),
            fechaCreacion: $this->parseDate($row['FECHA_CREACION'] ?? null),
            precioTotal: $this->parseMoney($row['PRECIO_TOTAL'] ?? null),
            pagadoTotal: $this->parseMoney($row['PAGADO_TOTAL'] ?? null),
            deudaTotal: $this->parseMoney($row['DEUDA_TOTAL'] ?? null),
        );
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

        $tokens = preg_split('/\s+/u', $fullName) ?: [];
        if (count($tokens) === 1) {
            return [$tokens[0], 'Sin apellido'];
        }
        if (count($tokens) === 2) {
            return [$tokens[0], $tokens[1]];
        }
        if (count($tokens) === 3) {
            return [implode(' ', array_slice($tokens, 0, 1)), implode(' ', array_slice($tokens, 1))];
        }

        return [
            implode(' ', array_slice($tokens, 0, count($tokens) - 2)),
            implode(' ', array_slice($tokens, -2)),
        ];
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

    private function normalizeScalar(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_float($value) || is_int($value)) {
            if (floor((float) $value) === (float) $value) {
                return (string) (int) $value;
            }

            return rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? '';

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeDocument(mixed $value): ?string
    {
        $normalized = $this->normalizeScalar($value);
        if ($normalized === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $normalized) ?? '';

        return $digits !== '' ? $digits : $normalized;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $normalized = $this->normalizeScalar($value);

        return $normalized !== null ? preg_replace('/\s+/', '', $normalized) : null;
    }

    private function normalizeEmail(mixed $value): ?string
    {
        $normalized = $this->normalizeScalar($value);

        return $normalized !== null ? Str::lower($normalized) : null;
    }

    private function parseMoney(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }

        $normalized = str_replace(['S/', ' ', ','], ['', '', '.'], (string) $value);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';

        if ($normalized === '' || ! is_numeric($normalized)) {
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
            return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value));
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        foreach ([
            'd/m/Y H:i:s A',
            'd/m/Y h:i:s A',
            'd/m/Y H:i A',
            'd/m/Y h:i A',
            'd/m/Y',
            'Y-m-d H:i:s',
            'Y-m-d',
        ] as $format) {
            try {
                return CarbonImmutable::createFromFormat($format, $normalized);
            } catch (\Throwable) {
            }
        }

        try {
            return CarbonImmutable::parse($normalized);
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizeHeader(string $header): string
    {
        $header = Str::ascii($header);
        $header = mb_strtoupper(trim($header), 'UTF-8');
        $header = str_replace(['_', '.'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return trim($header);
    }
}
