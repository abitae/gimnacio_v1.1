<?php

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\User;
use App\Services\Imports\ExcelSociosReader;
use App\Services\Imports\SociosActivosImportService;
use Database\Seeders\BaseCatalogSeeder;
use Illuminate\Support\Facades\Artisan;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(BaseCatalogSeeder::class);
});

it('fails when the excel headers are incomplete', function () {
    $file = createSociosExcelFile([
        [
            'CODIGO' => '1',
            'NOMBRES' => 'Ana',
            'APELLIDOS' => 'Prueba',
            'CORREO' => '',
            'DNI' => '12345678',
        ],
    ], [
        'CODIGO',
        'NOMBRES',
        'APELLIDOS',
        'CORREO',
        'DNI',
    ]);

    expect(fn () => app(ExcelSociosReader::class)->read($file))
        ->toThrow(RuntimeException::class);
});

it('creates mirrored memberships from paquete names', function () {
    $file = createSociosExcelFile([
        socioRow(paquete: 'LOW COST 2025', costo: '150', fechaInicio: '01/03/2026', fechaFin: '31/03/2026'),
        socioRow(paquete: 'LOW COST 2025', costo: '150', fechaInicio: '01/04/2026', fechaFin: '01/05/2026'),
        socioRow(paquete: 'PLAN VIAJERO', costo: '220', fechaInicio: '02/02/2026', fechaFin: '01/04/2026'),
    ]);

    $report = app(SociosActivosImportService::class)->run('membresias', $file, true);

    expect($report['created'])->toBe(2);
    expect(Membresia::query()->where('nombre', 'LOW COST 2025')->exists())->toBeTrue();
    expect(Membresia::query()->where('nombre', 'PLAN VIAJERO')->exists())->toBeTrue();
});

it('creates minimal users for vendedores and repartidos', function () {
    $file = createSociosExcelFile([
        socioRow(dni: '12345678', vendedor: 'yolanda', repartido: 'liliana'),
        socioRow(dni: '12345679', vendedor: 'YOLANDA', repartido: ''),
        socioRow(dni: '12345680', vendedor: 'SIN VENDEDOR', repartido: 'PATTY'),
    ]);

    $report = app(SociosActivosImportService::class)->run('users', $file, true);

    expect($report['created'])->toBe(3);
    expect(User::query()->where('name', 'yolanda')->exists())->toBeTrue();
    expect(User::query()->where('name', 'liliana')->exists())->toBeTrue();
    expect(User::query()->where('name', 'PATTY')->exists())->toBeTrue();
    expect(User::query()->where('email', 'import.fallback@local.test')->exists())->toBeTrue();
});

it('imports clients with active membership enrollment and payment', function () {
    Membresia::factory()->create([
        'nombre' => 'LOW COST 2025',
        'duracion_dias' => 30,
        'precio_base' => 150,
        'estado' => 'activa',
    ]);

    $file = createSociosExcelFile([
        socioRow(
            dni: '12345678',
            nombres: 'Ana',
            apellidos: 'Prueba',
            paquete: 'LOW COST 2025',
            costo: '150',
            fechaInscripcion: '28/02/2026 04:13 PM',
            fechaInicio: '02/03/2026',
            fechaFin: '01/04/2026',
            vendedor: 'rosana',
            origen: 'Walk in'
        ),
    ]);

    $report = app(SociosActivosImportService::class)->run('clients', $file, true);

    expect($report['created_clients'])->toBe(1);
    expect($report['created_matriculas'])->toBe(1);
    expect($report['created_pagos'])->toBe(1);

    $cliente = Cliente::query()->where('numero_documento', '12345678')->firstOrFail();
    $matricula = ClienteMatricula::query()->where('cliente_id', $cliente->id)->firstOrFail();
    $pago = Pago::query()->where('cliente_id', $cliente->id)->firstOrFail();

    expect($cliente->estado_cliente)->toBe('activo');
    expect($matricula->membresia?->nombre)->toBe('LOW COST 2025');
    expect((float) $pago->saldo_pendiente)->toBe(0.0);
});

it('updates existing clients and skips duplicate memberships', function () {
    $membership = Membresia::factory()->create([
        'nombre' => 'LOW COST 2025',
        'duracion_dias' => 30,
        'precio_base' => 150,
        'estado' => 'activa',
    ]);

    $user = User::factory()->create(['estado' => 'activo']);
    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '12345678',
        'nombres' => 'Ana',
        'apellidos' => 'Prueba',
        'telefono' => null,
        'email' => null,
        'direccion' => null,
        'estado_cliente' => 'inactivo',
        'created_by' => $user->id,
    ]);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membership->id,
        'fecha_matricula' => '2026-02-28',
        'fecha_inicio' => '2026-03-02',
        'fecha_fin' => '2026-04-01',
        'estado' => 'activa',
        'precio_lista' => 150,
        'descuento_monto' => 0,
        'precio_final' => 150,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
    ]);

    $file = createSociosExcelFile([
        socioRow(
            dni: '12345678',
            nombres: 'Ana',
            apellidos: 'Prueba',
            correo: 'ana@example.test',
            celular: '51999999999',
            paquete: 'LOW COST 2025',
            costo: '150',
            fechaInscripcion: '28/02/2026 04:13 PM',
            fechaInicio: '02/03/2026',
            fechaFin: '01/04/2026',
            vendedor: 'rosana'
        ),
    ]);

    $report = app(SociosActivosImportService::class)->run('clients', $file, true);

    $cliente->refresh();

    expect($report['updated_clients'])->toBe(1);
    expect($report['omitted_duplicate_matriculas'])->toBe(1);
    expect($cliente->telefono)->toBe('51999999999');
    expect($cliente->email)->toBe('ana@example.test');
    expect($cliente->estado_cliente)->toBe('activo');
    expect(ClienteMatricula::query()->where('cliente_id', $cliente->id)->count())->toBe(1);
});

it('supports the artisan command in dry run mode', function () {
    $file = createSociosExcelFile([
        socioRow(paquete: 'LOW COST 2025', costo: '150', fechaInicio: '01/03/2026', fechaFin: '31/03/2026'),
    ]);

    $exitCode = Artisan::call('import:socios-activos', [
        '--phase' => 'membresias',
        '--file' => $file,
        '--dry-run' => true,
    ]);

    expect($exitCode)->toBe(0);
    expect(Artisan::output())->toContain('Fase: membresias');
    expect(Membresia::query()->where('nombre', 'LOW COST 2025')->exists())->toBeFalse();
});

it('reads html exports with xls extension', function () {
    $file = createSociosHtmlXlsFile([
        socioRow(
            dni: '88888888',
            paquete: 'PLAN VIAJERO',
            costo: '220.00',
            fechaInscripcion: '31/01/2026 05:56:0 PM',
            fechaInicio: '02/02/2026',
            fechaFin: '01/04/2026',
            vendedor: 'yolanda',
            repartido: 'liliana'
        ),
    ]);

    $report = app(SociosActivosImportService::class)->run('membresias', $file, false);

    expect($report['detected_packages'])->toBe(1);
    expect($report['packages'][0]['package'])->toBe('PLAN VIAJERO');
});

function socioRow(
    string $codigo = '1001',
    string $nombres = 'Ana',
    string $apellidos = 'Prueba',
    string $correo = '',
    string $dni = '12345678',
    string $edad = '30',
    string $celular = '51999999999',
    string $fechaNacimiento = '01/01/1996',
    string $direccion = '',
    string $tipoVenta = 'MEMBRESÍA',
    string $origen = 'Walk in',
    string $paquete = 'LOW COST 2025',
    string $fechaInscripcion = '28/02/2026 04:13 PM',
    string $costo = '150',
    string $fechaInicio = '02/03/2026',
    string $fechaFin = '01/04/2026',
    string $vendedor = 'rosana',
    string $repartido = '',
    string $sesiones = '0',
    string $asistencias = '0',
    string $reservas = '0',
): array {
    return [
        'CODIGO' => $codigo,
        'NOMBRES' => $nombres,
        'APELLIDOS' => $apellidos,
        'CORREO' => $correo,
        'DNI' => $dni,
        'EDAD' => $edad,
        'CELULAR' => $celular,
        'F NACIMIENTO' => $fechaNacimiento,
        'DIRECCION' => $direccion,
        'TIPO DE VENTA' => $tipoVenta,
        'ORIGEN' => $origen,
        'PAQUETE' => $paquete,
        'F. INSCRIPCIÓN' => $fechaInscripcion,
        'COSTO' => $costo,
        'FECHA INICIO' => $fechaInicio,
        'FECHA FIN' => $fechaFin,
        'VENDEDOR' => $vendedor,
        'REPARTIDO' => $repartido,
        'SESIONES' => $sesiones,
        'ASISTENCIAS' => $asistencias,
        'RESERVAS' => $reservas,
    ];
}

function createSociosExcelFile(array $rows, ?array $headers = null): string
{
    $headers ??= [
        'CODIGO',
        'NOMBRES',
        'APELLIDOS',
        'CORREO',
        'DNI',
        'EDAD',
        'CELULAR',
        'F NACIMIENTO',
        'DIRECCION',
        'TIPO DE VENTA',
        'ORIGEN',
        'PAQUETE',
        'F. INSCRIPCIÓN',
        'COSTO',
        'FECHA INICIO',
        'FECHA FIN',
        'VENDEDOR',
        'REPARTIDO',
        'SESIONES',
        'ASISTENCIAS',
        'RESERVAS',
    ];

    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->fromArray([['Listado de Socios Activos']], null, 'A1');
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

    $path = storage_path('framework/testing/'.uniqid('socios-activos-', true).'.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($path);

    return $path;
}

function createSociosHtmlXlsFile(array $rows): string
{
    $headers = [
        'CODIGO',
        'NOMBRES',
        'APELLIDOS',
        'CORREO',
        'DNI',
        'EDAD',
        'CELULAR',
        'F NACIMIENTO',
        'DIRECCION',
        'TIPO DE VENTA',
        'ORIGEN',
        'PAQUETE',
        'F. INSCRIPCIÓN',
        'COSTO',
        'FECHA INICIO',
        'FECHA FIN',
        'VENDEDOR',
        'REPARTIDO',
        'SESIONES',
        'ASISTENCIAS',
        'RESERVAS',
    ];

    $html = "<!DOCTYPE html><html><head><meta charset='UTF-8'></head><body><table><thead>";
    $html .= "<tr><td colspan='21'>Listado de Socios Activos</td></tr><tr>";
    foreach ($headers as $header) {
        $html .= '<th>'.$header.'</th>';
    }
    $html .= '</tr></thead><tbody>';

    foreach ($rows as $row) {
        $html .= '<tr>';
        foreach (array_keys(socioRow()) as $key) {
            $html .= '<td>'.($row[$key] ?? '').'</td>';
        }
        $html .= '</tr>';
    }

    $html .= '</tbody></table></body></html>';

    $path = storage_path('framework/testing/'.uniqid('socios-html-', true).'.xls');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    file_put_contents($path, $html);

    return $path;
}
