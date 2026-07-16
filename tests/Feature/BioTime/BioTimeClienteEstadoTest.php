<?php

declare(strict_types=1);

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

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('activates estado_cliente when cliente has vigente clase', function () {
    $sucursal = estadoSucursal('act');
    $user = User::factory()->create();
    $clase = Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'inactivo',
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

    $updated = app(BioTimeClienteEstadoService::class)->activateEstadoCliente($cliente);

    expect($updated->estado_cliente)->toBe('activo');
});

it('refuses activate estado_cliente without subscription', function () {
    $sucursal = estadoSucursal('no-sub');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'inactivo',
    ]);

    expect(fn () => app(BioTimeClienteEstadoService::class)->activateEstadoCliente($cliente))
        ->toThrow(InvalidArgumentException::class);
});

it('deactivates estado_cliente without touching biotime commands', function () {
    $sucursal = estadoSucursal('deact');
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'estado_cliente' => 'activo',
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

    $updated = app(BioTimeClienteEstadoService::class)->deactivateEstadoCliente($cliente);

    expect($updated->estado_cliente)->toBe('inactivo')
        ->and(\App\Models\BioTime\BioTimeAccessCommand::query()->count())->toBe(0);
});
