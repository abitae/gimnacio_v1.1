<?php

use App\Services\Imports\ExcelContratosDeudaReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

uses(TestCase::class);

it('reads contratos deuda sheet for membership imports', function () {
    $path = tempnam(sys_get_temp_dir(), 'contratos-deuda-').'.xlsx';

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Contratos Deuda');
    $spreadsheet->getActiveSheet()->fromArray([
        ['CÓDIGO', 'NOMBRES', 'CELULAR', 'MEMBRESÍA', 'VENDEDOR', 'F. INICIO', 'F. FIN', 'PRECIO', 'PAGADO', 'DEUDA', 'MONTO CUOTA', 'CUOTAS PEND.', 'ESTADO'],
        ['1', 'ANDREA ,PUENTE BRICEÑO', '51936140000', 'low cost 2025', 'SIN VENDEDOR', 45791, 46151, 1279, 289, 990, 99, 10, 'EN DEUDA'],
    ]);

    (new Xlsx($spreadsheet))->save($path);

    try {
        $rows = app(ExcelContratosDeudaReader::class)->read($path);

        expect($rows)->toHaveCount(1)
            ->and($rows[0]->codigo)->toBe('1')
            ->and($rows[0]->nombres)->toBe('ANDREA')
            ->and($rows[0]->apellidos)->toBe('PUENTE BRICEÑO')
            ->and($rows[0]->paquete)->toBe('low cost 2025')
            ->and($rows[0]->fechaInicio)->not->toBeNull()
            ->and($rows[0]->fechaFin)->not->toBeNull()
            ->and($rows[0]->costo)->toBe(1279.0);
    } finally {
        @unlink($path);
    }
});
