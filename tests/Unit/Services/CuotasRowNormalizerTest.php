<?php

use App\Services\Imports\CuotasRowNormalizer;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

test('normalizer reads MONTO CUOTA and FECHA CUOTA column aliases', function () {
    $n = new CuotasRowNormalizer;
    $row = [
        'CODIGO' => '100',
        'MEMBRESIA' => 'Plan X',
        'MONTO CUOTA' => 50.5,
        'FECHA CUOTA' => '15/03/2026',
    ];
    $dto = $n->normalize(5, $row);
    expect($dto->montoCuota)->toBe(50.5)
        ->and($dto->fechaCuota?->format('Y-m-d'))->toBe('2026-03-15');
});

test('normalizer parses Excel serial date for FECHA CUOTA', function () {
    $n = new CuotasRowNormalizer;
    $serial = (float) ExcelDate::dateTimeToExcel(new \DateTimeImmutable('2026-04-20'));
    $row = [
        'CODIGO' => '1',
        'MEMBRESIA' => 'Plan',
        'M. CUOTA' => 100,
        'FECHA CUOTA' => $serial,
    ];
    $dto = $n->normalize(2, $row);
    expect($dto->fechaCuota?->format('Y-m-d'))->toBe('2026-04-20')
        ->and($dto->montoCuota)->toBe(100.0);
});

test('normalizer matches headers with NBSP and case variants', function () {
    $n = new CuotasRowNormalizer;
    $row = [
        "fecha\xC2\xA0cuota" => '10/01/2026',
        'MONTO  CUOTA' => '25,50',
    ];
    $dto = $n->normalize(3, $row);
    expect($dto->fechaCuota)->not->toBeNull()
        ->and($dto->montoCuota)->toBe(25.5);
});
