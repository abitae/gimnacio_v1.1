<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use App\Services\Imports\DeudasClientesImportService;
use App\Services\Imports\ExcelDeudasReader;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
});

it('fails when debt excel headers are incomplete', function () {
    $file = createDebtExcelFile([
        ['CODIGO' => '1', 'NOMBRES' => 'Ana', 'DNI' => '12345678'],
    ], ['CODIGO', 'NOMBRES', 'DNI']);

    expect(fn () => app(ExcelDeudasReader::class)->read($file))
        ->toThrow(RuntimeException::class);
});

it('matches exact matricula and updates saldo pendiente', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $cliente = Cliente::factory()->create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '12345678',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $membresia = Membresia::factory()->create([
        'nombre' => 'LOW COST 2025',
        'precio_base' => 150,
        'estado' => 'activa',
    ]);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-03-01',
        'fecha_inicio' => '2026-03-01',
        'fecha_fin' => '2026-03-31',
        'estado' => 'activa',
        'precio_lista' => 150,
        'descuento_monto' => 0,
        'precio_final' => 150,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);
    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 150,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => '2026-03-01 10:00:00',
        'es_pago_parcial' => false,
        'saldo_pendiente' => 0,
        'registrado_por' => $user->id,
    ]);

    $file = createDebtExcelFile([
        debtRow(
            dni: '12345678',
            plan: 'LOW COST 2025',
            fechaInicio: '01/03/2026',
            fechaFin: '31/03/2026',
            costo: '150',
            debe: '50',
            vendedor: 'rosana'
        ),
    ]);

    $report = app(DeudasClientesImportService::class)->run($file, true);

    $matricula->refresh();
    $latestPago = $matricula->pagos()->latest('created_at')->firstOrFail();

    expect($report['matched_exact'])->toBe(1);
    expect($report['updated_matriculas'])->toBe(1);
    expect($report['updated_pagos'])->toBe(1);
    expect((float) $matricula->saldo_pendiente_actual)->toBe(50.0);
    expect((float) $latestPago->monto)->toBe(100.0);
    expect((float) $latestPago->saldo_pendiente)->toBe(50.0);
    expect($matricula->pagos()->count())->toBe(1);
});

it('matches flexibly by plan and date tolerance', function () {
    $user = User::factory()->create(['estado' => 'activo']);
    $cliente = Cliente::factory()->create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '87654321',
        'created_by' => $user->id,
        'updated_by' => $user->id,
    ]);
    $membresia = Membresia::factory()->create([
        'nombre' => 'LOW COST-ESPECIAL',
        'precio_base' => 200,
        'estado' => 'activa',
    ]);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-03-02',
        'fecha_inicio' => '2026-03-02',
        'fecha_fin' => '2026-04-01',
        'estado' => 'activa',
        'precio_lista' => 200,
        'descuento_monto' => 0,
        'precio_final' => 200,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);
    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 200,
        'moneda' => 'PEN',
        'metodo_pago' => 'efectivo',
        'fecha_pago' => '2026-03-02 10:00:00',
        'es_pago_parcial' => false,
        'saldo_pendiente' => 0,
        'registrado_por' => $user->id,
    ]);

    $file = createDebtExcelFile([
        debtRow(
            dni: '87654321',
            plan: 'low cost especial',
            fechaInicio: '01/03/2026',
            fechaFin: '31/03/2026',
            costo: '200',
            debe: '80',
            vendedor: 'yolanda'
        ),
    ]);

    $report = app(DeudasClientesImportService::class)->run($file, true);

    $matricula->refresh();

    expect($report['matched_flexible'])->toBe(1);
    expect((float) $matricula->saldo_pendiente_actual)->toBe(80.0);
});

it('reports missing clients and personalizados without mutating data', function () {
    $file = createDebtExcelFile([
        debtRow(dni: '11111111', plan: 'LOW COST 2025', costo: '150', debe: '50'),
        debtRow(dni: '22222222', tipoPlan: 'PERSONALIZADO', plan: 'Plan custom', costo: '300', debe: '100'),
    ]);

    $report = app(DeudasClientesImportService::class)->run($file, false);

    expect($report['no_cliente'])->toBe(1);
    expect($report['personalizados'])->toBe(1);
    expect($report['updated_matriculas'])->toBe(0);
    expect($report['updated_pagos'])->toBe(0);
});

it('supports the artisan command in dry run mode', function () {
    $file = createDebtExcelFile([
        debtRow(dni: '11111111', plan: 'LOW COST 2025', costo: '150', debe: '50'),
    ]);

    $exitCode = Artisan::call('import:deudas-clientes', [
        '--file' => $file,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Fase: deudas-clientes');
});

it('reads html xls debt exports', function () {
    $file = createDebtHtmlXlsFile([
        debtRow(dni: '12345678', plan: 'LOW COST 2025', costo: '150', debe: '75'),
    ]);

    $report = app(DeudasClientesImportService::class)->run($file, false);

    expect($report['processed_rows'])->toBe(1);
    expect($report['no_cliente'])->toBe(1);
});

function debtRow(
    string $codigo = '1001',
    string $nombres = 'Ana Prueba',
    string $correo = '',
    string $dni = '12345678',
    string $celular = '51999999999',
    string $tipoPlan = 'MEMBRESÍA',
    string $plan = 'LOW COST 2025',
    string $fechaInicio = '01/03/2026',
    string $fechaFin = '31/03/2026',
    string $costo = '150',
    string $debe = '50',
    string $vendedor = 'rosana',
): array {
    return [
        'CODIGO' => $codigo,
        'NOMBRES' => $nombres,
        'CORREO' => $correo,
        'DNI' => $dni,
        'CELULAR' => $celular,
        'TIPO PLAN' => $tipoPlan,
        'PLAN' => $plan,
        'FECHA INICIO' => $fechaInicio,
        'FECHA FIN' => $fechaFin,
        'COSTO' => $costo,
        'DEBE' => $debe,
        'VENDEDOR' => $vendedor,
    ];
}

function createDebtExcelFile(array $rows, ?array $headers = null): string
{
    $headers ??= [
        'CODIGO',
        'NOMBRES',
        'CORREO',
        'DNI',
        'CELULAR',
        'TIPO PLAN',
        'PLAN',
        'FECHA INICIO',
        'FECHA FIN',
        'COSTO',
        'DEBE',
        'VENDEDOR',
    ];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([['Informe de Deudas']], null, 'A1');
    $sheet->fromArray([$headers], null, 'A2');

    $rowIndex = 3;
    foreach ($rows as $row) {
        $ordered = [];
        foreach ($headers as $header) {
            $ordered[] = $row[$header] ?? '';
        }
        $sheet->fromArray([$ordered], null, 'A'.$rowIndex);
        $rowIndex++;
    }

    $path = storage_path('framework/testing/'.uniqid('deudas-clientes-', true).'.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($path);

    return $path;
}

function createDebtHtmlXlsFile(array $rows): string
{
    $headers = [
        'CODIGO',
        'NOMBRES',
        'CORREO',
        'DNI',
        'CELULAR',
        'TIPO PLAN',
        'PLAN',
        'FECHA INICIO',
        'FECHA FIN',
        'COSTO',
        'DEBE',
        'VENDEDOR',
    ];

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><table><thead>";
    $html .= "<tr><td colspan='12'>Informe de Deudas</td></tr><tr>";
    foreach ($headers as $header) {
        $html .= '<th>'.$header.'</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach ($headers as $header) {
            $html .= '<td>'.($row[$header] ?? '').'</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></body></html>';

    $path = storage_path('framework/testing/'.uniqid('deudas-html-', true).'.xls');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, $html);

    return $path;
}
