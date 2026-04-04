<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\DeudaClienteRowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class DeudaRowNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function normalize(int $rowNumber, array $row): DeudaClienteRowData
    {
        return new DeudaClienteRowData(
            rowNumber: $rowNumber,
            codigo: $this->cleanString($row['CODIGO'] ?? null),
            nombreRaw: $this->cleanString($row['NOMBRES'] ?? null),
            correo: $this->cleanEmail($row['CORREO'] ?? null),
            dni: $this->cleanDni($row['DNI'] ?? null),
            celular: $this->cleanString($row['CELULAR'] ?? null),
            tipoPlan: $this->cleanString($row['TIPO PLAN'] ?? null),
            plan: $this->cleanString($row['PLAN'] ?? null),
            fechaInicio: $this->parseDate($row['FECHA INICIO'] ?? null),
            fechaFin: $this->parseDate($row['FECHA FIN'] ?? null),
            costo: $this->parseMoney($row['COSTO'] ?? null),
            debe: $this->parseMoney($row['DEBE'] ?? null),
            vendedor: $this->cleanString($row['VENDEDOR'] ?? null),
        );
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
