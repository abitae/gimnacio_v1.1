<?php

namespace App\Services\Imports;

use App\DataTransferObjects\Imports\CuotaClienteRowData;
use Carbon\CarbonImmutable;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class CuotasRowNormalizer
{
    /**
     * @param  array<string, mixed>  $row
     */
    public function normalize(int $rowNumber, array $row): CuotaClienteRowData
    {
        $indexed = $this->indexRowByNormalizedHeader($row);

        return new CuotaClienteRowData(
            rowNumber: $rowNumber,
            codigo: $this->cleanString($this->pick($row, $indexed, ['CODIGO'])),
            nombres: $this->cleanString($this->pick($row, $indexed, ['NOMBRES'])),
            celular: $this->cleanString($this->pick($row, $indexed, ['CELULAR'])),
            membresia: $this->cleanString($this->pick($row, $indexed, ['MEMBRESIA'])),
            fechaInicio: $this->parseDate($this->pick($row, $indexed, ['FECHA INICIO', 'FECHA_INICIO', 'FECHA DE INICIO'])),
            fechaFin: $this->parseDate($this->pick($row, $indexed, ['FECHA FIN', 'FECHA_FIN', 'FECHA DE FIN'])),
            vendedor: $this->cleanString($this->pick($row, $indexed, ['VENDEDOR'])),
            precio: $this->parseMoney($this->pick($row, $indexed, ['PRECIO'])),
            pago: $this->parseMoney($this->pick($row, $indexed, ['PAGO'])),
            fechaCuota: $this->parseDate($this->pick($row, $indexed, [
                'FECHA CUOTA',
                'FECHA_CUOTA',
                'FECHA DE CUOTA',
                'FECHA VENCIMIENTO',
                'VENCIMIENTO',
            ])),
            debe: $this->parseMoney($this->pick($row, $indexed, ['DEBE'])),
            montoCuota: $this->parseMoney($this->pick($row, $indexed, [
                'M. CUOTA',
                'M.CUOTA',
                'M CUOTA',
                'MONTO CUOTA',
                'MONTO DE CUOTA',
                'MONTO_CUOTA',
                'VALOR CUOTA',
                'IMPORTE CUOTA',
            ])),
            dni: $this->cleanString($this->pick($row, $indexed, ['DNI', 'NUMERO DOCUMENTO', 'NÚMERO DOCUMENTO', 'DOCUMENTO'])),
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
            if (array_key_exists($key, $indexed)) {
                $v = $indexed[$key];
                if ($v !== null && $v !== '') {
                    return $v;
                }
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

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            $n = (float) $value;
            if ($n >= 1 && $n < 1000000) {
                try {
                    return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject($n))->startOfDay();
                } catch (\Throwable) {
                }
            }
        }

        $value = $this->cleanString($value);
        if ($value === null) {
            return null;
        }

        $formats = ['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y', 'Y/m/d'];
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
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value) || is_float($value)) {
            return round((float) $value, 2);
        }
        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $normalized = str_ireplace(['s/', 'pen'], '', trim((string) $value));
        $normalized = str_replace([' ', ','], ['', '.'], $normalized);
        $normalized = preg_replace('/[^0-9.\-]/', '', $normalized) ?? '';
        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        return round((float) $normalized, 2);
    }
}
