<?php

use App\Services\Imports\ExcelColumnAnalyzerService;
use App\Support\Imports\ImportType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

uses(TestCase::class);

it('detects ready headers for cuotas import', function () {
    $path = tempnam(sys_get_temp_dir(), 'cuotas-analysis-');

    file_put_contents($path, <<<'HTML'
<!DOCTYPE html>
<html>
<body>
<table>
    <tr><td>Reporte legacy</td></tr>
    <tr>
        <td>CODIGO</td>
        <td>CLIENTE</td>
        <td>CELULAR</td>
        <td>MEMBRESIA</td>
        <td>FECHA_INICIO</td>
        <td>FECHA_FIN</td>
        <td>VENDEDOR</td>
        <td>PRECIO</td>
        <td>PAGO</td>
        <td>FECHA_CUOTA</td>
        <td>DEBE</td>
        <td>M_CUOTA</td>
        <td>OBSERVACION</td>
    </tr>
    <tr>
        <td>C001</td>
        <td>Juan Perez</td>
        <td>999999999</td>
        <td>Plan Gold</td>
        <td>2026-01-01</td>
        <td>2026-03-31</td>
        <td>Vendedor 1</td>
        <td>300</td>
        <td>100</td>
        <td>2026-02-01</td>
        <td>200</td>
        <td>100</td>
        <td>Primera cuota</td>
    </tr>
</table>
</body>
</html>
HTML);

    try {
        $analysis = app(ExcelColumnAnalyzerService::class)->analyze($path, ImportType::CUOTAS);

        expect($analysis['header_row'])->toBe(2)
            ->and($analysis['is_ready'])->toBeTrue()
            ->and($analysis['missing_headers'])->toBe([])
            ->and($analysis['extra_headers'])->toContain('OBSERVACION')
            ->and($analysis['detected_headers'])->toContain('FECHA_CUOTA');
    } finally {
        @unlink($path);
    }
});

it('analyzes clientes maestro sheet inside a multi-sheet workbook', function () {
    $path = tempnam(sys_get_temp_dir(), 'clientes-analysis-').'.xlsx';

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->setTitle('Resumen');
    $spreadsheet->getActiveSheet()->fromArray([
        ['Resumen general'],
        ['Indicador', 'Valor'],
    ]);

    $sheet = $spreadsheet->createSheet();
    $sheet->setTitle('Clientes Maestro');
    $sheet->fromArray([
        ['CODIGO', 'CLIENTE', 'DNI', 'CELULAR', 'CORREO', 'GENERO', 'EDAD', 'ORIGEN', 'ULTIMA_MEMBRESIA', 'COSTO', 'FECHA_INICIO', 'FECHA_FIN', 'ESTADO', 'ESTADO_FINAL', 'EN_REPORTE_ACTIVOS', 'VENDEDOR', 'FECHA_CREACION', 'PRECIO_TOTAL', 'PAGADO_TOTAL', 'DEUDA_TOTAL'],
        ['C002', 'MARIA LOPEZ PEREZ', '12345678', '999999999', 'maria@example.com', 'F', '30', 'Walk In', 'Plan Fit', '300', '2026-01-01', '2026-02-01', 'Inactivo', 'Activo', '1', 'ROMINA', '2026-01-01', '300', '100', '200'],
    ]);

    (new Xlsx($spreadsheet))->save($path);

    try {
        $analysis = app(ExcelColumnAnalyzerService::class)->analyze($path, ImportType::CLIENTES);

        expect($analysis['is_ready'])->toBeFalse()
            ->and($analysis['header_row'])->toBe(1)
            ->and($analysis['missing_headers'])->toContain('TIENE_DEUDA')
            ->and($analysis['detected_headers'])->toContain('CLIENTE')
            ->and($analysis['detected_headers'])->not->toContain('Indicador');
    } finally {
        @unlink($path);
    }
});
