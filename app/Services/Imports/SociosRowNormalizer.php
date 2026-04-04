<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\SocioActivoRowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class SociosRowNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function normalize(int $rowNumber, array $row): SocioActivoRowData
    {
        return new SocioActivoRowData(
            rowNumber: $rowNumber,
            codigo: $this->normalizeScalar($row['CODIGO'] ?? null),
            nombres: $this->normalizeScalar($row['NOMBRES'] ?? null),
            apellidos: $this->normalizeScalar($row['APELLIDOS'] ?? null),
            correo: $this->normalizeEmail($row['CORREO'] ?? null),
            dni: $this->normalizeDocument($row['DNI'] ?? null),
            edad: $this->normalizeScalar($row['EDAD'] ?? null),
            celular: $this->normalizePhone($row['CELULAR'] ?? null),
            fechaNacimiento: $this->parseDate($row['F NACIMIENTO'] ?? null),
            direccion: $this->normalizeScalar($row['DIRECCION'] ?? null),
            tipoVenta: $this->normalizeScalar($row['TIPO DE VENTA'] ?? null),
            origen: $this->normalizeScalar($row['ORIGEN'] ?? null),
            paquete: $this->normalizeScalar($row['PAQUETE'] ?? null),
            fechaInscripcion: $this->parseDate($row['F. INSCRIPCIÓN'] ?? null),
            costo: $this->parseMoney($row['COSTO'] ?? null),
            fechaInicio: $this->parseDate($row['FECHA INICIO'] ?? null),
            fechaFin: $this->parseDate($row['FECHA FIN'] ?? null),
            vendedor: $this->normalizeScalar($row['VENDEDOR'] ?? null),
            repartido: $this->normalizeScalar($row['REPARTIDO'] ?? null),
            sesiones: $this->normalizeScalar($row['SESIONES'] ?? null),
            asistencias: $this->normalizeScalar($row['ASISTENCIAS'] ?? null),
            reservas: $this->normalizeScalar($row['RESERVAS'] ?? null),
        );
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

        $normalized = trim($this->repairEncoding((string) $value));
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

        $normalized = str_replace(
            ["\xc2\xa0", "\xe2\x80\xaf", 'a. m.', 'p. m.', 'a.m.', 'p.m.', 'A. M.', 'P. M.'],
            [' ', ' ', 'AM', 'PM', 'AM', 'PM', 'AM', 'PM'],
            $normalized
        );
        $normalized = preg_replace('/\s+/u', ' ', $normalized) ?? $normalized;

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

    private function repairEncoding(string $value): string
    {
        if (! preg_match('/Ã|Â|â/u', $value)) {
            return $value;
        }

        $converted = @iconv('Windows-1252', 'UTF-8//IGNORE', $value);

        return is_string($converted) && $converted !== '' ? $converted : $value;
    }
}
