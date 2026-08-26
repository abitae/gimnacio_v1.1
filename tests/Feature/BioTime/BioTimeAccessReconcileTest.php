<?php

declare(strict_types=1);

use App\Jobs\BioTime\ReconcileBioTimeAccessForSucursal;
use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeAccessCommandService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

function reconcileSucursal(string $suffix = ''): Sucursal
{
    $codigo = 'rc-'.Str::lower(Str::random(6)).($suffix !== '' ? '-'.$suffix : '');
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa Rec '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal Rec',
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

function reconcileMatricula(
    Cliente $cliente,
    Sucursal $sucursal,
    User $user,
    Membresia $membresia,
    array $overrides = [],
    bool $fireEvents = false
): ClienteMatricula {
    $attributes = array_merge([
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
    ], $overrides);

    if ($fireEvents) {
        return ClienteMatricula::query()->create($attributes);
    }

    return ClienteMatricula::withoutEvents(
        fn () => ClienteMatricula::query()->create($attributes)
    );
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));
});

afterEach(function (): void {
    Carbon::setTestNow();
});

it('reconciles eligible cliente into pending activate', function () {
    $sucursal = reconcileSucursal('act');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'enabled' => true,
        'area_biotime_id' => 2,
    ])->save();

    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-ACT-001'),
    ]);
    reconcileMatricula($cliente, $sucursal, $user, $membresia);

    $result = app(BioTimeAccessCommandService::class)->reconcileSucursal($sucursal->id);

    expect($result['activated'])->toBe(1)
        ->and($result['skipped'])->toBeFalse()
        ->and(BioTimeAccessCommand::query()
            ->where('cliente_id', $cliente->id)
            ->where('action', 'activate')
            ->where('status', 'pending')
            ->where('desired_area_biotime_id', 2)
            ->where('emp_code', 'RC-ACT-001')
            ->exists())->toBeTrue();
});

it('enqueues deactivate when cliente becomes vencido after being active', function () {
    $sucursal = reconcileSucursal('venc');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'enabled' => true,
        'area_biotime_id' => 2,
    ])->save();

    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-VENC-001'),
    ]);
    $matricula = reconcileMatricula($cliente, $sucursal, $user, $membresia);

    $service = app(BioTimeAccessCommandService::class);
    $service->reconcileSucursal($sucursal->id);

    BioTimeAccessCommand::query()
        ->where('cliente_id', $cliente->id)
        ->where('action', 'activate')
        ->update([
            'status' => BioTimeAccessCommand::STATUS_ACKED,
            'acked_at' => now(),
        ]);

    $matricula->forceFill(['fecha_fin' => '2026-06-01'])->saveQuietly();

    $result = $service->reconcileSucursal($sucursal->id);

    expect($result['deactivated'])->toBe(1)
        ->and(BioTimeAccessCommand::query()
            ->where('cliente_id', $cliente->id)
            ->where('action', 'deactivate')
            ->where('status', 'pending')
            ->exists())->toBeTrue();
});

it('enqueues activate again when matricula is reactivated', function () {
    $sucursal = reconcileSucursal('re');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'enabled' => true,
        'area_biotime_id' => 2,
    ])->save();

    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-RE-001'),
    ]);
    $matricula = reconcileMatricula($cliente, $sucursal, $user, $membresia, [
        'fecha_fin' => '2026-06-01',
    ]);

    BioTimeEmployee::query()->create([
        'biotime_id' => 9001,
        'emp_code' => 'RC-RE-001',
        'cliente_id' => $cliente->id,
        'first_name' => 'Test',
    ]);

    $service = app(BioTimeAccessCommandService::class);
    $service->reconcileSucursal($sucursal->id);

    expect(BioTimeAccessCommand::query()
        ->where('cliente_id', $cliente->id)
        ->where('action', 'deactivate')
        ->where('status', 'pending')
        ->exists())->toBeTrue();

    BioTimeAccessCommand::query()
        ->where('cliente_id', $cliente->id)
        ->where('action', 'deactivate')
        ->update(['status' => BioTimeAccessCommand::STATUS_ACKED, 'acked_at' => now()]);

    $matricula->forceFill(['fecha_fin' => '2026-12-31', 'estado' => 'activa'])->saveQuietly();

    $result = $service->reconcileSucursal($sucursal->id);

    expect($result['activated'])->toBe(1)
        ->and(BioTimeAccessCommand::query()
            ->where('cliente_id', $cliente->id)
            ->where('action', 'activate')
            ->where('status', 'pending')
            ->exists())->toBeTrue();
});

it('skips reconcile when sucursal setting is disabled', function () {
    $sucursal = reconcileSucursal('off');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill(['enabled' => false])->save();

    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-OFF-001'),
    ]);
    reconcileMatricula($cliente, $sucursal, $user, $membresia);

    $result = app(BioTimeAccessCommandService::class)->reconcileSucursal($sucursal->id);

    expect($result['skipped'])->toBeTrue()
        ->and(BioTimeAccessCommand::query()->where('sucursal_id', $sucursal->id)->count())->toBe(0);
});

it('dispatches reconcile job when cliente matricula is saved', function () {
    Queue::fake();

    $sucursal = reconcileSucursal('hook');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill(['enabled' => true])->save();
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-HOOK-001'),
    ]);

    reconcileMatricula($cliente, $sucursal, $user, $membresia, fireEvents: true);

    Queue::assertPushed(ReconcileBioTimeAccessForSucursal::class, function (ReconcileBioTimeAccessForSucursal $job) use ($sucursal) {
        return $job->sucursalId === $sucursal->id;
    });
});

it('dispatches reconcile job when clase matricula is saved', function () {
    Queue::fake();

    $sucursal = reconcileSucursal('hook-clase');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill(['enabled' => true])->save();
    $user = User::factory()->create();
    $clase = \App\Models\Core\Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-HOOK-CLASE'),
    ]);

    ClienteMatricula::query()->create([
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
    ]);

    Queue::assertPushed(ReconcileBioTimeAccessForSucursal::class, function (ReconcileBioTimeAccessForSucursal $job) use ($sucursal) {
        return $job->sucursalId === $sucursal->id;
    });
});

it('reconciles eligible clase-only cliente into pending activate with ensure_create', function () {
    $sucursal = reconcileSucursal('clase-act');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'enabled' => true,
        'area_biotime_id' => 2,
    ])->save();

    $user = User::factory()->create();
    $clase = \App\Models\Core\Clase::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-CLASE-001'),
        'nombres' => 'Ana',
        'apellidos' => 'Clase',
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

    $result = app(BioTimeAccessCommandService::class)->reconcileSucursal($sucursal->id);

    $command = BioTimeAccessCommand::query()
        ->where('cliente_id', $cliente->id)
        ->where('action', 'activate')
        ->where('status', 'pending')
        ->first();

    expect($result['activated'])->toBe(1)
        ->and($command)->not->toBeNull()
        ->and($command->ensure_create)->toBeTrue()
        ->and($command->first_name)->toBe('Ana')
        ->and($command->last_name)->toBe('Clase');
});

it('never deletes inactive biometric identities to free capacity', function () {
    $sucursal = reconcileSucursal('cupo');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'enabled' => true,
        'area_biotime_id' => 2,
        'employee_limit' => 1,
        'employees_count' => 1,
    ])->save();

    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);

    $inactive = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-PURGE-OLD'),
    ]);
    BioTimeEmployee::query()->create([
        'biotime_id' => 8001,
        'emp_code' => 'RC-PURGE-OLD',
        'cliente_id' => $inactive->id,
        'first_name' => 'Old',
    ]);
    BioTimeAccessCommand::query()->create([
        'sucursal_id' => $sucursal->id,
        'cliente_id' => $inactive->id,
        'emp_code' => 'RC-PURGE-OLD',
        'action' => BioTimeAccessCommand::ACTION_DEACTIVATE,
        'status' => BioTimeAccessCommand::STATUS_ACKED,
        'acked_at' => now()->subDays(10),
        'ensure_create' => false,
    ]);

    $active = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RC-PURGE-NEW'),
        'nombres' => 'Nuevo',
        'apellidos' => 'Cliente',
    ]);
    reconcileMatricula($active, $sucursal, $user, $membresia);

    $command = app(BioTimeAccessCommandService::class)->enqueue(
        $sucursal,
        $active,
        BioTimeAccessCommand::ACTION_ACTIVATE
    );

    expect($command)->not->toBeNull()
        ->and(BioTimeAccessCommand::query()
            ->where('cliente_id', $inactive->id)
            ->where('action', BioTimeAccessCommand::ACTION_DELETE)
            ->where('status', 'pending')
            ->exists())->toBeFalse()
        ->and(BioTimeEmployee::query()
            ->where('sucursal_id', $sucursal->id)
            ->where('emp_code', 'RC-PURGE-OLD')
            ->exists())->toBeTrue();
});
