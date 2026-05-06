<?php

use App\Services\Imports\ExcelCuotasLegacyReader;
use App\Services\Imports\ExcelDeudasReader;
use App\Services\Imports\ExcelVendedorColumnReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

uses(TestCase::class);

it('reads vendedores from usuarios vendedores sheet', function () {
    $path = tempnam(sys_get_temp_dir(), 'usuarios-vendedores-').'.xlsx';

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Resumen');
    $spreadsheet->getActiveSheet()->fromArray([['Resumen']]);

    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Usuarios Vendedores');
    $sheet->fromArray([
        ['VENDEDOR', 'CLIENTES', 'ACTIVOS'],
        ['ROMINA', 15, 10],
        ['SIN VENDEDOR', 5, 0],
        ['YOLANDA', 8, 6],
    ]);

    (new Xlsx($spreadsheet))->save($path);

    try {
        $rows = app(ExcelVendedorColumnReader::class)->read($path);

        expect($rows)->toHaveCount(2)
            ->and($rows[0]['nombre'])->toBe('ROMINA')
            ->and($rows[1]['nombre'])->toBe('YOLANDA');
    } finally {
        @unlink($path);
    }
});

it('reads deudas clientes sheet with underscore headers and excel dates', function () {
    $path = tempnam(sys_get_temp_dir(), 'deudas-clientes-').'.xlsx';

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Deudas Clientes');
    $spreadsheet->getActiveSheet()->fromArray([
        ['CODIGO', 'CLIENTE', 'CORREO', 'DNI', 'CELULAR', 'TIPO_PLAN', 'PLAN', 'FECHA_INICIO', 'FECHA_FIN', 'COSTO', 'DEBE', 'VENDEDOR'],
        ['5866', 'Lisney Ludeña', 'lisney@example.com', '47582132', '51937453944', 'MEMBRESÍA', 'plan viajero', 46125, 46185, 220, 110, 'FERNANDA'],
    ]);

    (new Xlsx($spreadsheet))->save($path);

    try {
        $rows = app(ExcelDeudasReader::class)->read($path);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]->nombreRaw)->toBe('Lisney Ludeña')
            ->and($rows[0]->tipoPlan)->toBe('MEMBRESÍA')
            ->and($rows[0]->plan)->toBe('plan viajero')
            ->and($rows[0]->fechaInicio)->not->toBeNull()
            ->and($rows[0]->debe)->toBe(110.0);
    } finally {
        @unlink($path);
    }
});

it('reads cuotas from detalle cuotas sheet with consolidated headers', function () {
    $path = tempnam(sys_get_temp_dir(), 'detalle-cuotas-').'.xlsx';

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Detalle Cuotas');
    $spreadsheet->getActiveSheet()->fromArray([
        ['CODIGO', 'CLIENTE', 'CELULAR', 'MEMBRESIA', 'FECHA_INICIO', 'FECHA_FIN', 'VENDEDOR', 'PRECIO', 'PAGO', 'FECHA_CUOTA', 'DEBE', 'M_CUOTA'],
        ['1', 'ANDREA PUENTE', '51936140000', 'LOW COST 2025', 45791, 46151, 'SIN VENDEDOR', 1279, 289, 46126, 990, 99],
    ]);

    (new Xlsx($spreadsheet))->save($path);

    try {
        $rows = app(ExcelCuotasLegacyReader::class)->read($path);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]->membresia)->toBe('LOW COST 2025')
            ->and($rows[0]->montoCuota)->toBe(99.0)
            ->and($rows[0]->fechaCuota)->not->toBeNull();
    } finally {
        @unlink($path);
    }
});
