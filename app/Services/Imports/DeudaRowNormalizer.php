<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class DeudaRowNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function normalize(int $rowNumber, array $row): DeudaClienteRowData
    {
        $indexed = $this->indexRowByNormalizedHeader($row);

        return new DeudaClienteRowData(
            rowNumber: $rowNumber,
            codigo: $this->cleanString($this->pick($row, $indexed, ['CODIGO'])),
            nombreRaw: $this->cleanString($this->pick($row, $indexed, ['NOMBRES', 'CLIENTE'])),
            correo: $this->cleanEmail($this->pick($row, $indexed, ['CORREO'])),
            dni: $this->cleanDni($this->pick($row, $indexed, ['DNI'])),
            celular: $this->cleanString($this->pick($row, $indexed, ['CELULAR'])),
            tipoPlan: $this->cleanString($this->pick($row, $indexed, ['TIPO PLAN', 'TIPO_PLAN'])),
            plan: $this->cleanString($this->pick($row, $indexed, ['PLAN'])),
            fechaInicio: $this->parseDate($this->pick($row, $indexed, ['FECHA INICIO', 'FECHA_INICIO'])),
            fechaFin: $this->parseDate($this->pick($row, $indexed, ['FECHA FIN', 'FECHA_FIN'])),
            costo: $this->parseMoney($this->pick($row, $indexed, ['COSTO'])),
            debe: $this->parseMoney($this->pick($row, $indexed, ['DEBE'])),
            vendedor: $this->cleanString($this->pick($row, $indexed, ['VENDEDOR'])),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $indexed
     * @param  list<string>  $candidates
     */
    private function pick(array $row, array $indexed, array $candidates): mixed
    {
        foreach ($candidates as $name) {
            if (array_key_exists($name, $row) && $row[$name] !== null && $row[$name] !== '') {
                return $row[$name];
            }
        }
        foreach ($candidates as $name) {
            $key = $this->normalizeHeaderKey($name);
            if (array_key_exists($key, $indexed) && $indexed[$key] !== null && $indexed[$key] !== '') {
                return $indexed[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function indexRowByNormalizedHeader(array $row): array
    {
        $indexed = [];
        foreach ($row as $header => $value) {
            $indexed[$this->normalizeHeaderKey((string) $header)] = $value;
        }

        return $indexed;
    }

    private function normalizeHeaderKey(string $header): string
    {
        $header = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $header);
        $header = Str::ascii($header);
        $header = mb_strtoupper(trim($header), 'UTF-8');
        $header = str_replace(['_', '.'], ' ', $header);
        $header = preg_replace('/\s+/u', ' ', $header) ?? $header;

        return trim($header);
    }

    private function cleanString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }

    private function cleanEmail(mixed $value): ?string
    {
        $email = $this->cleanString($value);

        return $email ? Str::lower($email) : null;
    }

    private function cleanDni(mixed $value): ?string
    {
        $raw = $this->cleanString($value);
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        return $digits !== '' ? $digits : null;
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
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

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d'];
        foreach ($formats as $format) {
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

    private function parseMoney(mixed $value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(['S/', '$', ',', ' '], '', $value);

        return is_numeric($value) ? round((float) $value, 2) : null;
    }
}
