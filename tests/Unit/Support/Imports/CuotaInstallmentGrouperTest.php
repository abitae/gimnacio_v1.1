<?php

use App\DataTransferObjects\Imports\CuotaClienteRowData;
use App\Support\Imports\CuotaInstallmentGrouper;
use Carbon\CarbonImmutable;

it('agrupa filas por codigo membresia y fechas en orden de primera aparicion', function () {
    $fi = CarbonImmutable::parse('2026-01-01');
    $ff = CarbonImmutable::parse('2026-01-31');
    $rows = [
        new CuotaClienteRowData(2, 'A1', null, null, 'Gold', $fi, $ff, null, 100.0, 0.0, CarbonImmutable::parse('2026-01-10'), 100.0, 50.0),
        new CuotaClienteRowData(3, 'B2', null, null, 'Silver', $fi, $ff, null, 80.0, 0.0, CarbonImmutable::parse('2026-01-11'), 80.0, 40.0),
        new CuotaClienteRowData(4, 'A1', null, null, 'Gold', $fi, $ff, null, 100.0, 50.0, CarbonImmutable::parse('2026-01-20'), 50.0, 50.0),
    ];

    $groups = CuotaInstallmentGrouper::groupOrdered($rows);

    expect(array_keys($groups))->toHaveCount(2)
        ->and($groups[CuotaInstallmentGrouper::groupKey($rows[0])])->toHaveCount(2)
        ->and($groups[CuotaInstallmentGrouper::groupKey($rows[1])])->toHaveCount(1);
});
