<?php

namespace App\Support\Imports;

use App\DataTransferObjects\Imports\CuotaClienteRowData;

final class CuotaInstallmentGrouper
{
    public static function groupKey(CuotaClienteRowData $row): string
    {
        $cod = trim((string) ($row->codigo ?? ''));
        $mem = mb_strtolower(trim((string) ($row->membresia ?? '')));
        $fi = $row->fechaInicio?->toDateString() ?? '';
        $ff = $row->fechaFin?->toDateString() ?? '';

        return "{$cod}|{$mem}|{$fi}|{$ff}";
    }

    /**
     * Agrupa filas conservando el orden de primera aparición de cada grupo.
     *
     * @param  list<CuotaClienteRowData>  $rows
     * @return array<string, list<CuotaClienteRowData>>
     */
    public static function groupOrdered(array $rows): array
    {
        $bucket = [];
        foreach ($rows as $row) {
            $k = self::groupKey($row);
            $bucket[$k][] = $row;
        }

        $ordered = [];
        $seen = [];
        foreach ($rows as $row) {
            $k = self::groupKey($row);
            if (! isset($seen[$k])) {
                $seen[$k] = true;
                $ordered[$k] = $bucket[$k];
            }
        }

        return $ordered;
    }
}
