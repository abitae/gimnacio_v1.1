<?php

declare(strict_types=1);

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeAccessEligibilityService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

function eligibilitySucursal(string $suffix = ''): Sucursal
{
    $codigo = 'el-'.Str::lower(Str::random(6)).($suffix !== '' ? '-'.$suffix : '');
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa Elig '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal Elig',
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

function eligibilityMatricula(
    Cliente $cliente,
    Sucursal $sucursal,
    User $user,
    Membresia $membresia,
    array $overrides = []
): ClienteMatricula {
    return ClienteMatricula::query()->create(array_merge([
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
    ], $overrides));
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('marks cliente with activa vigente matricula as eligible', function () {
    $sucursal = eligibilitySucursal('ok');
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);
    eligibilityMatricula($cliente, $sucursal, $user, $membresia);

    $service = app(BioTimeAccessEligibilityService::class);

    expect($service->isEligible($cliente, $sucursal->id))->toBeTrue()
        ->and($service->listEligibleClienteIds($sucursal->id)->all())->toContain($cliente->id);
});

it('rejects vencida by fecha_fin even if estado is activa', function () {
    $sucursal = eligibilitySucursal('venc');
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);
    eligibilityMatricula($cliente, $sucursal, $user, $membresia, [
        'fecha_fin' => '2026-06-01',
    ]);

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sucursal->id))->toBeFalse();
});

it('rejects congelada matricula', function () {
    $sucursal = eligibilitySucursal('cong');
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);
    eligibilityMatricula($cliente, $sucursal, $user, $membresia, [
        'estado' => 'congelada',
    ]);

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sucursal->id))->toBeFalse();
});

it('rejects cliente from another sucursal', function () {
    $sedeA = eligibilitySucursal('a');
    $sedeB = eligibilitySucursal('b');
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sedeB->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sedeB->id, 'created_by' => $user->id]);
    eligibilityMatricula($cliente, $sedeB, $user, $membresia);

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sedeA->id))->toBeFalse()
        ->and(app(BioTimeAccessEligibilityService::class)->listEligibleClienteIds($sedeA->id)->all())->not->toContain($cliente->id);
});

it('rejects cliente without matricula', function () {
    $sucursal = eligibilitySucursal('none');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sucursal->id))->toBeFalse()
        ->and(app(BioTimeAccessEligibilityService::class)->listEligibleClienteIds($sucursal->id))->toBeEmpty();
});

it('marks cliente with activa clase matricula as eligible', function () {
    $sucursal = eligibilitySucursal('clase');
    $user = User::factory()->create();
    $clase = \App\Models\Core\Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);

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

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sucursal->id))->toBeTrue();
});

it('rejects cancelada clase matricula', function () {
    $sucursal = eligibilitySucursal('clase-cancel');
    $user = User::factory()->create();
    $clase = \App\Models\Core\Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);

    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'clase',
        'clase_id' => $clase->id,
        'membresia_id' => null,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => null,
        'estado' => 'cancelada',
        'precio_lista' => 50,
        'descuento_monto' => 0,
        'precio_final' => 50,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sucursal->id,
    ]));

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sucursal->id))->toBeFalse();
});

it('rejects clase with fecha_fin in the past', function () {
    $sucursal = eligibilitySucursal('clase-venc');
    $user = User::factory()->create();
    $clase = \App\Models\Core\Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create(['sucursal_id' => $sucursal->id, 'created_by' => $user->id]);

    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'clase',
        'clase_id' => $clase->id,
        'membresia_id' => null,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-06-01',
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

    expect(app(BioTimeAccessEligibilityService::class)->isEligible($cliente, $sucursal->id))->toBeFalse();
});
