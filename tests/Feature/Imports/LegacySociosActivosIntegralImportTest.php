<?php

use App\Livewire\Imports\Dashboard;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\Imports\ImportManagerService;
use App\Support\PermissionCatalog;
use App\Support\Imports\ImportType;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('builds a consolidated preview with phase summaries and skipped non-membership rows', function () {
    [$user, $sucursal] = createImportAdminAndSucursal();
    $this->actingAs($user);

    $uploaded = makeUploadedSociosExcel([
        socioIntegralRow(
            codigo: 'CLI-001',
            dni: '12345678',
            nombres: 'Ana',
            apellidos: 'Prueba',
            paquete: 'PLAN FULL',
            vendedor: 'rosana',
            repartido: 'patty'
        ),
        socioIntegralRow(
            codigo: 'CLI-002',
            dni: '87654321',
            nombres: 'Luis',
            apellidos: 'Funcional',
            tipoVenta: 'ENTRENAMIENTO FUNCIONAL',
            paquete: 'FUNCIONAL',
            vendedor: 'steysi'
        ),
    ]);

    $out = app(ImportManagerService::class)->preview($uploaded, ImportType::SOCIOS_ACTIVOS_INTEGRAL, $sucursal->id, [
        'duplicate_mode' => 'crear_o_actualizar',
    ]);

    $result = $out['result'];

    expect($result['phase_summaries']['usuarios']['detectados'])->toBe(2)
        ->and($result['phase_summaries']['clientes']['importadas'])->toBe(1)
        ->and($result['phase_summaries']['membresias']['catalogo_a_crear'])->toBe(1)
        ->and($result['phase_summaries']['membresias']['omitidas'])->toBe(1)
        ->and(collect($result['row_results'])->where('phase', 'membresias')->where('estado', 'skipped')->count())->toBe(1);
});

it('commits the integral flow creating users, clients, memberships, enrollment and payment', function () {
    [$user, $sucursal] = createImportAdminAndSucursal();
    $this->actingAs($user);

    $uploaded = makeUploadedSociosExcel([
        socioIntegralRow(
            codigo: 'CLI-100',
            dni: '44556677',
            nombres: 'Mario',
            apellidos: 'Integral',
            paquete: 'PLAN VIAJERO',
            costo: '220',
            vendedor: 'yolanda',
            repartido: 'PATTY'
        ),
    ]);

    $preview = app(ImportManagerService::class)->preview($uploaded, ImportType::SOCIOS_ACTIVOS_INTEGRAL, $sucursal->id, [
        'duplicate_mode' => 'crear_o_actualizar',
    ]);

    $commit = app(ImportManagerService::class)->commit($preview['import'], [
        'duplicate_mode' => 'crear_o_actualizar',
    ]);

    $cliente = Cliente::query()->where('codigo', 'CLI-100')->first();
    $membresia = Membresia::query()->where('nombre', 'PLAN VIAJERO')->where('sucursal_id', $sucursal->id)->first();
    $asesor = User::query()->where('name', 'yolanda')->first();

    expect($cliente)->not->toBeNull()
        ->and($cliente->sucursal_id)->toBe($sucursal->id)
        ->and($membresia)->not->toBeNull()
        ->and($asesor)->not->toBeNull()
        ->and($asesor->sucursales()->whereKey($sucursal->id)->exists())->toBeTrue()
        ->and(ClienteMatricula::query()->where('cliente_id', $cliente->id)->count())->toBe(1)
        ->and(Pago::query()->where('cliente_id', $cliente->id)->count())->toBe(1)
        ->and(($commit['import']->opciones['phase_summaries']['membresias']['catalogo_creadas'] ?? 0))->toBe(1);
});

it('does not duplicate the same membership enrollment on reimport and supports livewire preview', function () {
    [$user, $sucursal] = createImportAdminAndSucursal();
    $this->actingAs($user);

    $uploaded = makeUploadedSociosExcel([
        socioIntegralRow(
            codigo: 'CLI-200',
            dni: '99887766',
            nombres: 'Laura',
            apellidos: 'Reimport',
            paquete: 'LOW COST 2025',
            vendedor: 'fernanda'
        ),
    ]);

    $manager = app(ImportManagerService::class);
    $first = $manager->preview($uploaded, ImportType::SOCIOS_ACTIVOS_INTEGRAL, $sucursal->id, [
        'duplicate_mode' => 'crear_o_actualizar',
    ]);
    $manager->commit($first['import'], ['duplicate_mode' => 'crear_o_actualizar']);

    $uploadedAgain = makeUploadedSociosExcel([
        socioIntegralRow(
            codigo: 'CLI-200',
            dni: '99887766',
            nombres: 'Laura',
            apellidos: 'Reimport',
            paquete: 'LOW COST 2025',
            vendedor: 'fernanda'
        ),
    ]);

    $second = $manager->preview($uploadedAgain, ImportType::SOCIOS_ACTIVOS_INTEGRAL, $sucursal->id, [
        'duplicate_mode' => 'crear_o_actualizar',
    ]);
    $manager->commit($second['import'], ['duplicate_mode' => 'crear_o_actualizar']);

    $cliente = Cliente::query()->where('codigo', 'CLI-200')->firstOrFail();

    expect(ClienteMatricula::query()->where('cliente_id', $cliente->id)->count())->toBe(1)
        ->and(($second['result']['phase_summaries']['membresias']['omitidas'] ?? 0))->toBeGreaterThan(0);

    Livewire::test(Dashboard::class)
        ->set('sucursalId', $sucursal->id)
        ->set('tipo', ImportType::SOCIOS_ACTIVOS_INTEGRAL)
        ->set('duplicateMode', 'crear_o_actualizar')
        ->set('archivo', makeUploadedSociosExcel([
            socioIntegralRow(
                codigo: 'CLI-201',
                dni: '11223344',
                nombres: 'Vista',
                apellidos: 'Previa',
                paquete: 'PLAN EXPRESS',
                vendedor: 'romina'
            ),
        ]))
        ->call('validar')
        ->assertSet('resultadoPreview.phase_summaries.usuarios.detectados', 1)
        ->assertSee('Usuarios')
        ->assertSee('Membresias y matriculas');
});

function createImportAdminAndSucursal(): array
{
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa import test',
        'razon_social' => 'Empresa import test',
        'estado' => 'activa',
    ]);

    $sucursal = Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'import-'.substr(md5((string) microtime(true)), 0, 8),
        'nombre' => 'Sucursal Import',
        'estado' => 'activa',
        'es_principal' => true,
    ]);

    $user = User::factory()->withoutTwoFactor()->create([
        'default_sucursal_id' => $sucursal->id,
    ]);
    $user->assignRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME);
    $user->sucursales()->syncWithoutDetaching([$sucursal->id]);

    return [$user, $sucursal];
}

function socioIntegralRow(
    string $codigo = '1001',
    string $nombres = 'Ana',
    string $apellidos = 'Prueba',
    string $correo = '',
    string $dni = '12345678',
    string $edad = '30',
    string $celular = '51999999999',
    string $fechaNacimiento = '01/01/1996',
    string $direccion = '',
    string $tipoVenta = 'MEMBRESIA',
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

function makeUploadedSociosExcel(array $rows): UploadedFile
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

    $path = storage_path('framework/testing/'.uniqid('socios-integral-', true).'.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0777, true);
    }

    $writer = new Xlsx($spreadsheet);
    $writer->save($path);

    return new UploadedFile($path, 'socios-activos.xlsx', null, null, true);
}
