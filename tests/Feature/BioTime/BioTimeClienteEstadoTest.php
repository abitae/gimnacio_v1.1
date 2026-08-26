<?php

declare(strict_types=1);

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Clase;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeClienteEstadoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function estadoSucursal(string $suffix = ''): Sucursal
{
    $codigo = 'es-'.Str::lower(Str::random(6)).($suffix !== '' ? '-'.$suffix : '');
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa Est '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal Est',
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

function enableBioTimeSede(Sucursal $sucursal, int $areaId = 2): void
{
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'enabled' => true,
        'area_biotime_id' => $areaId,
        'employee_limit' => 5000,
    ])->save();
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('activates estado_cliente when cliente has vigente clase', function () {
    $sucursal = estadoSucursal('act');
    enableBioTimeSede($sucursal);
    $user = User::factory()->create();
    $clase = Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'inactivo',
        ...biotimeIdentity('EST-ACT-001'),
    ]);

    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'clase',
        'clase_id' => $clase->id,
        'membresia_id' => null,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => null,
        'estado' => 'activa',
        'precio_lista' => 50,
        'descuento_monto' => 0,
        'precio_final' => 50,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]));

    $result = app(BioTimeClienteEstadoService::class)->activateEstadoCliente($cliente);

    expect($result['cliente']->estado_cliente)->toBe('activo')
        ->and($result['biotime_command'])->not->toBeNull()
        ->and($result['biotime_command']->action)->toBe(BioTimeAccessCommand::ACTION_ACTIVATE)
        ->and($result['biotime_command']->emp_code)->toBe('EST-ACT-001')
        ->and($result['biotime_command']->ensure_create)->toBeTrue()
        ->and($result['biotime_command']->desired_area_biotime_id)->toBe(2);
});

it('refuses activate estado_cliente without subscription', function () {
    $sucursal = estadoSucursal('no-sub');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'inactivo',
        ...biotimeIdentity('EST-NOSUB'),
    ]);

    expect(fn () => app(BioTimeClienteEstadoService::class)->activateEstadoCliente($cliente))
        ->toThrow(InvalidArgumentException::class);
});

it('activates without biotime command when cliente has no numero_documento', function () {
    $sucursal = estadoSucursal('nocod');
    enableBioTimeSede($sucursal);
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'inactivo',
        'codigo' => null,
        'numero_documento' => '   ',
    ]);

    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]));

    $result = app(BioTimeClienteEstadoService::class)->activateEstadoCliente($cliente);

    expect($result['cliente']->estado_cliente)->toBe('activo')
        ->and($result['biotime_command'])->toBeNull()
        ->and(BioTimeAccessCommand::query()->where('cliente_id', $cliente->id)->count())->toBe(0);
});

it('deactivates and enqueues biotime deactivate when employee is known', function () {
    $sucursal = estadoSucursal('deact');
    enableBioTimeSede($sucursal);
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'activo',
        ...biotimeIdentity('EST-DEACT-001'),
    ]);

    BioTimeEmployee::query()->create([
        'biotime_id' => 9101,
        'emp_code' => 'EST-DEACT-001',
        'cliente_id' => $cliente->id,
        'first_name' => 'Test',
    ]);

    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]));

    $result = app(BioTimeClienteEstadoService::class)->deactivateEstadoCliente($cliente);

    expect($result['cliente']->estado_cliente)->toBe('inactivo')
        ->and($result['biotime_command'])->not->toBeNull()
        ->and($result['biotime_command']->action)->toBe(BioTimeAccessCommand::ACTION_DEACTIVATE)
        ->and($result['biotime_command']->emp_code)->toBe('EST-DEACT-001');
});

it('deactivates and enqueues biotime deactivate even when employee is unknown locally', function () {
    $sucursal = estadoSucursal('deact-unk');
    enableBioTimeSede($sucursal);
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'activo',
        ...biotimeIdentity('EST-UNK-001'),
        'biotime_id' => null,
    ]);

    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-12-31',
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]));

    $result = app(BioTimeClienteEstadoService::class)->deactivateEstadoCliente($cliente);

    expect($result['cliente']->estado_cliente)->toBe('inactivo')
        ->and($result['biotime_command'])->not->toBeNull()
        ->and($result['biotime_command']->action)->toBe(BioTimeAccessCommand::ACTION_DEACTIVATE)
        ->and($result['biotime_command']->emp_code)->toBe('EST-UNK-001');
});
