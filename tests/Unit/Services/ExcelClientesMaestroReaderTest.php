<?php

use App\Services\Imports\ExcelClientesMaestroReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

uses(TestCase::class);

it('reads clientes maestro sheet and normalizes client names', function () {
    $path = tempnam(sys_get_temp_dir(), 'clientes-maestro-').'.xlsx';

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Resumen');
    $spreadsheet->getActiveSheet()->fromArray([
        ['Resumen'],
    ]);

    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Clientes Maestro');
    $sheet->fromArray([
        ['CODIGO', 'CLIENTE', 'DNI', 'CELULAR', 'CORREO', 'GENERO', 'EDAD', 'ORIGEN', 'ULTIMA_MEMBRESIA', 'COSTO', 'FECHA_INICIO', 'FECHA_FIN', 'ESTADO', 'ESTADO_FINAL', 'EN_REPORTE_ACTIVOS', 'VENDEDOR', 'FECHA_CREACION', 'PRECIO_TOTAL', 'PAGADO_TOTAL', 'DEUDA_TOTAL', 'TIENE_DEUDA'],
        ['15', 'MARIA JOSE LOPEZ PEREZ', '70112233', '+51999888777', 'maria@example.com', 'F', '29', 'Walk In', 'LOW COST 2025', 1279, 45915, 46275, 'Inactivo', 'Activo', 1, 'ROMINA', 45900, 1629, 289, 1340, 1],
    ]);

    (new Xlsx($spreadsheet))->save($path);

    try {
        $rows = app(ExcelClientesMaestroReader::class)->read($path);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]->codigo)->toBe('15')
            ->and($rows[0]->nombres)->toBe('MARIA JOSE')
            ->and($rows[0]->apellidos)->toBe('LOPEZ PEREZ')
            ->and($rows[0]->paquete)->toBe('LOW COST 2025')
            ->and($rows[0]->estadoFinal)->toBe('Activo')
            ->and($rows[0]->precioTotal)->toBe(1629.0)
            ->and($rows[0]->deudaTotal)->toBe(1340.0);
    } finally {
        @unlink($path);
    }
});
