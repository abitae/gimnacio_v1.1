<?php

declare(strict_types=1);

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeArea;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeAccessCommandService;
use App\Services\BioTime\BioTimeCapacityService;
use Illuminate\Support\Facades\DB;

function capacitySucursal(string $code): Sucursal
{
    $empresa = Empresa::query()->create(['nombre' => 'Capacity '.$code, 'estado' => 'activa']);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $code,
        'nombre' => 'Capacity '.$code,
        'estado' => 'activa',
        'es_principal' => false,
    ]);
}

function capacitySetting(Sucursal $sucursal, string $token): BioTimeSucursalSetting
{
    $setting = BioTimeSucursalSetting::forSucursal($sucursal->id);
    $setting->forceFill([
        'webhook_secret' => $token,
        'enabled' => true,
        'area_biotime_id' => 2,
        'denied_area_biotime_id' => 1,
        'employee_limit' => 500,
    ])->save();

    return $setting;
}

it('isolates identical external ids from independent BioTime installations', function () {
    $a = capacitySucursal('iso-a');
    $b = capacitySucursal('iso-b');
    capacitySetting($a, 'iso-token-a');
    capacitySetting($b, 'iso-token-b');

    $payload = [
        'entity' => 'employees',
        'timestamp' => now()->toIso8601String(),
        'data' => [['id' => 77, 'emp_code' => 'UNMANAGED-77', 'first_name' => 'Mirror']],
    ];

    $this->postJson('/api/biotime/sync', $payload, ['Authorization' => 'Bearer iso-token-a'])->assertOk();
    $this->postJson('/api/biotime/sync', $payload, ['Authorization' => 'Bearer iso-token-b'])->assertOk();
    foreach (['iso-token-a', 'iso-token-b'] as $token) {
        $this->postJson('/api/biotime/sync', [
            'entity' => 'areas',
            'timestamp' => now()->toIso8601String(),
            'data' => [['id' => 9, 'area_code' => 'SHARED', 'area_name' => 'Shared']],
        ], ['Authorization' => 'Bearer '.$token])->assertOk();
        $this->postJson('/api/biotime/sync', [
            'entity' => 'devices',
            'timestamp' => now()->toIso8601String(),
            'data' => [['id' => 11, 'sn' => 'SAME-SERIAL', 'alias' => 'Clock']],
        ], ['Authorization' => 'Bearer '.$token])->assertOk();
    }

    expect(BioTimeEmployee::query()->where('biotime_id', 77)->count())->toBe(2)
        ->and(BioTimeArea::query()->where('biotime_id', 9)->count())->toBe(2)
        ->and(BioTimeDevice::query()->where('serial_number', 'SAME-SERIAL')->count())->toBe(2)
        ->and(BioTimeEmployee::query()->where('sucursal_id', $a->id)->where('biotime_id', 77)->exists())->toBeTrue()
        ->and(BioTimeEmployee::query()->where('sucursal_id', $b->id)->where('biotime_id', 77)->exists())->toBeTrue();
});

it('prioritizes the most recent active enrollment and leaves overflow waiting', function () {
    $sucursal = capacitySucursal('priority');
    $setting = capacitySetting($sucursal, 'priority-token');
    $setting->forceFill(['employee_limit' => 2])->save();
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);

    foreach ([10, 30, 20] as $daysAgo) {
        $cliente = Cliente::factory()->create([
            'sucursal_id' => $sucursal->id,
            'created_by' => $user->id,
            'codigo' => 'PRIO-'.$daysAgo,
        ]);
        ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresia->id,
            'fecha_matricula' => now()->subDays($daysAgo),
            'fecha_inicio' => now()->subDays($daysAgo),
            'fecha_fin' => now()->addMonth(),
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
    }

    $roster = app(BioTimeCapacityService::class)->rosterForSucursal($sucursal->id);

    expect($roster->firstWhere('emp_code', 'PRIO-10')['status'])->toBe('selected')
        ->and($roster->firstWhere('emp_code', 'PRIO-20')['status'])->toBe('selected')
        ->and($roster->firstWhere('emp_code', 'PRIO-30')['status'])->toBe('waiting');

    app(BioTimeAccessCommandService::class)->reconcileSucursal($sucursal->id);

    $apiRoster = $this->getJson('/api/biotime/roster', [
        'Authorization' => 'Bearer priority-token',
    ])->assertOk()->json('data');

    expect(BioTimeAccessCommand::query()
        ->where('sucursal_id', $sucursal->id)
        ->where('action', BioTimeAccessCommand::ACTION_ACTIVATE)
        ->count())->toBe(3)
        ->and(collect($apiRoster)->firstWhere('emp_code', 'PRIO-30')['active'])->toBeTrue();
});

it('selects exactly 500 of 501 eligible clients', function () {
    $sucursal = capacitySucursal('hard-500');
    capacitySetting($sucursal, 'hard-500-token');
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $now = now();

    $clientRows = [];
    for ($i = 1; $i <= 501; $i++) {
        $clientRows[] = [
            'sucursal_id' => $sucursal->id,
            'codigo' => 'HARD-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
            'tipo_documento' => 'DNI',
            'numero_documento' => '95'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
            'nombres' => 'Cliente',
            'apellidos' => (string) $i,
            'estado_cliente' => 'activo',
            'created_by' => $user->id,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    DB::table('clientes')->insert($clientRows);

    $clientes = DB::table('clientes')
        ->where('sucursal_id', $sucursal->id)
        ->orderBy('id')
        ->get(['id']);
    $matriculas = [];
    foreach ($clientes as $index => $cliente) {
        $matriculas[] = [
            'cliente_id' => $cliente->id,
            'tipo' => 'membresia',
            'membresia_id' => $membresia->id,
            'fecha_matricula' => $now->toDateString(),
            'fecha_inicio' => $now->copy()->subDays($index)->toDateString(),
            'fecha_fin' => $now->copy()->addYear()->toDateString(),
            'estado' => 'activa',
            'precio_lista' => 100,
            'descuento_monto' => 0,
            'precio_final' => 100,
            'modalidad_pago' => 'contado',
            'requiere_plan_cuotas' => false,
            'cuota_inicial_monto' => 0,
            'asesor_id' => $user->id,
            'sucursal_id' => $sucursal->id,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
    DB::table('cliente_matriculas')->insert($matriculas);

    $roster = app(BioTimeCapacityService::class)->rosterForSucursal($sucursal->id);

    expect($roster->where('status', 'selected')->count())->toBe(500)
        ->and($roster->where('status', 'waiting')->count())->toBe(1)
        ->and($roster->firstWhere('status', 'waiting')['emp_code'])->toBe('HARD-0501');
});

it('fails closed until every access clock has a fresh verified inventory', function () {
    $sucursal = capacitySucursal('strict');
    $setting = capacitySetting($sucursal, 'strict-token');
    $setting->forceFill(['capacity_enforcement_enabled' => true])->save();
    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sucursal->id]);
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'codigo' => 'STRICT-CLIENT',
    ]);
    ClienteMatricula::withoutEvents(fn () => ClienteMatricula::query()->create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => today(),
        'fecha_inicio' => today(),
        'fecha_fin' => today()->addMonth(),
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

    BioTimeDevice::query()->create([
        'sucursal_id' => $sucursal->id,
        'biotime_id' => 1,
        'serial_number' => 'STRICT-1',
        'access_enabled' => true,
        'capacity_limit' => 500,
        'inventory_verified' => false,
    ]);

    $blocked = app(BioTimeCapacityService::class)->rosterCapacity($sucursal->id);
    expect($blocked['inventory_ready'])->toBeFalse()
        ->and($blocked['client_slots'])->toBe(0);
    app(BioTimeAccessCommandService::class)->reconcileSucursal($sucursal->id);
    expect(BioTimeAccessCommand::query()
        ->where('sucursal_id', $sucursal->id)
        ->where('action', BioTimeAccessCommand::ACTION_ACTIVATE)
        ->count())->toBe(0);

    $this->postJson('/api/biotime/heartbeat', [
        'bridge_version' => 'test',
        'devices' => [[
            'biotime_id' => 1,
            'serial_number' => 'STRICT-1',
            'online' => true,
            'capacity' => 500,
            'employees_count' => 2,
            'employee_codes' => ['STAFF-1', 'STAFF-2'],
            'inventory_at' => now()->toIso8601String(),
            'inventory_source' => 'terminal_counter',
        ]],
    ], ['Authorization' => 'Bearer strict-token'])->assertOk();

    $device = BioTimeDevice::query()->where('sucursal_id', $sucursal->id)->where('serial_number', 'STRICT-1')->firstOrFail();
    $device->forceFill(['inventory_verified' => true])->save();

    $ready = app(BioTimeCapacityService::class)->rosterCapacity($sucursal->id);
    expect($ready['inventory_ready'])->toBeTrue()
        ->and($ready['client_slots'])->toBe(498)
        ->and($device->fresh()->protected_users_count)->toBe(2);

    $this->getJson('/api/biotime/config', [
        'Authorization' => 'Bearer strict-token',
    ])->assertOk()
        ->assertJsonPath('hard_limit', 500)
        ->assertJsonPath('capacity.inventory_ready', true)
        ->assertJsonPath('devices.0.inventory_source', 'terminal_counter');
});

it('uses the smallest available client quota across every clock in the branch', function () {
    $sucursal = capacitySucursal('multiple-clocks');
    $setting = capacitySetting($sucursal, 'multiple-token');
    $setting->forceFill(['capacity_enforcement_enabled' => true])->save();

    foreach ([
        ['serial' => 'CLOCK-500', 'capacity' => 500, 'protected' => 10],
        ['serial' => 'CLOCK-300', 'capacity' => 300, 'protected' => 5],
    ] as $index => $clock) {
        BioTimeDevice::query()->create([
            'sucursal_id' => $sucursal->id,
            'biotime_id' => $index + 1,
            'serial_number' => $clock['serial'],
            'state' => 1,
            'access_enabled' => true,
            'capacity_limit' => $clock['capacity'],
            'reported_users_count' => $clock['protected'],
            'protected_users_count' => $clock['protected'],
            'inventory_verified' => true,
            'inventory_source' => 'terminal_counter',
            'inventory_synced_at' => now(),
        ]);
    }

    $capacity = app(BioTimeCapacityService::class)->rosterCapacity($sucursal->id);

    expect($capacity['inventory_ready'])->toBeTrue()
        ->and($capacity['client_slots'])->toBe(295);
});
