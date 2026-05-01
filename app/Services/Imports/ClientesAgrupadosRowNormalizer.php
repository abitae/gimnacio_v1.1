<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\ClienteAgrupadoContractRowData;
use App\DataTransferObjects\Imports\ClienteAgrupadoSummaryRowData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ClientesAgrupadosRowNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function normalizeContract(int $rowNumber, array $row): ClienteAgrupadoContractRowData
    {
        $indexed = $this->indexRowByNormalizedHeader($row);

        return new ClienteAgrupadoContractRowData(
            rowNumber: $rowNumber,
            codigo: $this->normalizeCode($this->pick($row, $indexed, ['CODIGO', 'CÓDIGO'])),
            nombreCompleto: $this->cleanString($this->pick($row, $indexed, ['NOMBRES'])),
            celular: $this->normalizePhone($this->pick($row, $indexed, ['CELULAR'])),
            membresia: $this->cleanString($this->pick($row, $indexed, ['MEMBRESIA', 'MEMBRESÍA'])),
            vendedor: $this->cleanString($this->pick($row, $indexed, ['VENDEDOR'])),
            fechaInicio: $this->parseDate($this->pick($row, $indexed, ['F. INICIO', 'FECHA INICIO', 'F INICIO'])),
            fechaFin: $this->parseDate($this->pick($row, $indexed, ['F. FIN', 'FECHA FIN', 'F FIN'])),
            precio: $this->parseMoney($this->pick($row, $indexed, ['PRECIO'])),
            pagado: $this->parseMoney($this->pick($row, $indexed, ['PAGADO', 'PAGO'])),
            deuda: $this->parseMoney($this->pick($row, $indexed, ['DEUDA', 'DEUDA TOTAL', 'DEBE'])),
            montoCuota: $this->parseMoney($this->pick($row, $indexed, ['MONTO CUOTA', 'M. CUOTA', 'M CUOTA'])),
            cuotasPendientes: $this->parseInteger($this->pick($row, $indexed, ['CUOTAS PEND.', 'CUOTAS PEND', 'CUOTAS PENDIENTES'])),
            estadoExcel: $this->cleanString($this->pick($row, $indexed, ['ESTADO'])),
        );
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function normalizeSummary(int $rowNumber, array $row): ClienteAgrupadoSummaryRowData
    {
        $indexed = $this->indexRowByNormalizedHeader($row);

        return new ClienteAgrupadoSummaryRowData(
            rowNumber: $rowNumber,
            codigo: $this->normalizeCode($this->pick($row, $indexed, ['CODIGO', 'CÓDIGO'])),
            precioTotal: $this->parseMoney($this->pick($row, $indexed, ['PRECIO TOTAL'])),
            pagado: $this->parseMoney($this->pick($row, $indexed, ['PAGADO'])),
            deudaTotal: $this->parseMoney($this->pick($row, $indexed, ['DEUDA TOTAL'])),
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

        if (is_int($value) || is_float($value)) {
            $value = floor((float) $value) === (float) $value
                ? (string) (int) $value
                : rtrim(rtrim(number_format((float) $value, 8, '.', ''), '0'), '.');
        }

        $value = trim((string) $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return $value === '' ? null : $value;
    }

    private function normalizeCode(mixed $value): ?string
    {
        $code = $this->cleanString($value);

        return $code !== null ? mb_substr($code, 0, 50) : null;
    }

    private function normalizePhone(mixed $value): ?string
    {
        $phone = $this->cleanString($value);
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        return $digits !== '' ? mb_substr($digits, 0, 20) : mb_substr($phone, 0, 20);
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $number = (float) $value;
            if ($number > 0 && $number < 1000000) {
                try {
                    return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject($number))->startOfDay();
                } catch (\Throwable) {
                }
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

    private function parseInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value) || is_numeric($value)) {
            return max(0, (int) floor((float) $value));
        }

        $normalized = preg_replace('/[^0-9\-]/', '', (string) $value) ?? '';
        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        return max(0, (int) $normalized);
    }
}
