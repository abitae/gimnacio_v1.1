<?php

declare(strict_types=1);

use App\Jobs\BioTime\ReconcileBioTimeAccessForSucursal;
use App\Livewire\BioTime\BioTimeDashboard;
use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeMapping;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Models\BioTime\BioTimeSyncLog;
use App\Models\BioTime\BioTimeTransaction;
use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use App\Models\User;
use App\Services\BioTime\BioTimeSyncService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;

it('rejects invalid BioTime tokens', function () {
    $sucursal = biotimeSucursal();
    biotimeAgentSetting($sucursal, 'valid-token');

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [],
    ], ['Authorization' => 'Bearer invalid'])->assertUnauthorized();
});

it('rejects sync when sucursal setting is disabled', function () {
    $sucursal = biotimeSucursal('disabled-sede');
    biotimeAgentSetting($sucursal, 'disabled-token', enabled: false);

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [],
    ], ['Authorization' => 'Bearer disabled-token'])
        ->assertForbidden()
        ->assertJsonPath('sucursal_id', $sucursal->id);
});

it('accepts X-BioTime-Secret header', function () {
    Queue::fake();
    $sucursal = biotimeSucursal('header-sede');
    biotimeAgentSetting($sucursal, 'header-token');

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [
            ['id' => 1, 'dept_code' => 'ADM', 'dept_name' => 'Administracion'],
        ],
    ], ['X-BioTime-Secret' => 'header-token'])
        ->assertOk()
        ->assertJsonPath('sucursal_id', $sucursal->id);
});

it('validates the received entity', function () {
    $sucursal = biotimeSucursal('validate-sede');
    biotimeAgentSetting($sucursal, 'valid-token');

    $this->postJson('/api/biotime/sync', [
        'entity' => 'unknown',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [],
    ], ['Authorization' => 'Bearer valid-token'])->assertUnprocessable();
});

it('processes a department batch inline even when queue is enabled', function () {
    Queue::fake();
    config(['biotime.queue' => true]);

    $sucursal = biotimeSucursal('sync-sede');
    $setting = biotimeAgentSetting($sucursal, 'valid-token');

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [
            ['id' => 1, 'dept_code' => 'ADM', 'dept_name' => 'Administracion'],
        ],
    ], ['Authorization' => 'Bearer valid-token'])
        ->assertOk()
        ->assertJsonPath('queued', false)
        ->assertJsonPath('processed', 1)
        ->assertJsonPath('sucursal_id', $sucursal->id);

    Queue::assertNothingPushed();
    expect(BioTimeSyncBatch::query()->where('entity', 'departments')->exists())->toBeTrue()
        ->and(\App\Models\BioTime\BioTimeDepartment::query()->where('biotime_id', 1)->where('dept_code', 'ADM')->exists())->toBeTrue();

    $setting->refresh();
    expect($setting->last_received_at)->not->toBeNull()
        ->and($setting->last_heartbeat_at)->not->toBeNull();
});

it('stores areas and departments from embedded employee payload', function () {
    $sucursal = biotimeSucursal('emb-catalog');
    biotimeAgentSetting($sucursal, 'emb-catalog-token');

    app(BioTimeSyncService::class)->process('employees', '2026-07-16 12:00:00', [[
        'id' => 88,
        'emp_code' => 'EMB-01',
        'first_name' => 'Ana',
        'department' => ['id' => 7, 'dept_code' => 'GYM', 'dept_name' => 'Gimnasio'],
        'area' => [
            ['id' => 2, 'area_code' => 'AUTH', 'area_name' => 'Autorizada'],
            ['id' => 1, 'area_code' => 'DENY', 'area_name' => 'Denegada'],
        ],
    ]], (string) Str::uuid());

    expect(\App\Models\BioTime\BioTimeDepartment::query()->where('biotime_id', 7)->where('dept_name', 'Gimnasio')->exists())->toBeTrue()
        ->and(\App\Models\BioTime\BioTimeArea::query()->where('biotime_id', 2)->where('area_name', 'Autorizada')->exists())->toBeTrue()
        ->and(\App\Models\BioTime\BioTimeArea::query()->where('biotime_id', 1)->exists())->toBeTrue();
});

it('processes employees sync inline even when queue is enabled', function () {
    Queue::fake();
    config(['biotime.queue' => true]);

    $sucursal = biotimeSucursal('emp-inline');
    biotimeAgentSetting($sucursal, 'emp-inline-token');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('EMP-INLINE-1'),
        'biotime_id' => null,
    ]);

    $this->postJson('/api/biotime/sync', [
        'entity' => 'employees',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [
            [
                'id' => 55,
                'emp_code' => 'EMP-INLINE-1',
                'first_name' => 'Ana',
                'last_name' => 'Lopez',
                'department' => ['id' => 1, 'dept_name' => 'Gym'],
                'area' => [['id' => 2]],
            ],
        ],
    ], ['Authorization' => 'Bearer emp-inline-token'])
        ->assertOk()
        ->assertJsonPath('queued', false)
        ->assertJsonPath('processed', 1);

    Queue::assertNothingPushed();
    expect($cliente->fresh()->biotime_id)->toBe(55)
        ->and(\App\Models\BioTime\BioTimeEmployee::query()->where('biotime_id', 55)->where('cliente_id', $cliente->id)->exists())->toBeTrue();
});

it('processes transactions sync inline even when queue is enabled', function () {
    Queue::fake();
    config(['biotime.queue' => true]);

    $sucursal = biotimeSucursal('tx-inline');
    biotimeAgentSetting($sucursal, 'tx-inline-token');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('TX-INLINE-1'),
    ]);

    \App\Models\BioTime\BioTimeDevice::query()->create([
        'biotime_id' => 901,
        'serial_number' => 'SN-TX-INLINE',
        'alias' => 'Entrada',
        'access_role' => \App\Models\BioTime\BioTimeDevice::ACCESS_ROLE_ENTRADA,
        'is_attendance' => true,
    ]);
    BioTimeMapping::query()->create([
        'mapping_type' => 'device',
        'biotime_id' => 901,
        'target_type' => 'sucursal',
        'target_id' => $sucursal->id,
        'sucursal_id' => $sucursal->id,
    ]);

    $this->postJson('/api/biotime/sync', [
        'entity' => 'transactions',
        'timestamp' => '2026-07-16 10:00:00',
        'data' => [[
            'id' => 7001,
            'emp_code' => 'TX-INLINE-1',
            'punch_time' => now()->format('Y-m-d H:i:s'),
            'terminal_sn' => 'SN-TX-INLINE',
            'punch_state' => '0',
        ]],
    ], ['Authorization' => 'Bearer tx-inline-token'])
        ->assertOk()
        ->assertJsonPath('queued', false)
        ->assertJsonPath('processed', 1);

    Queue::assertNothingPushed();
    expect(\App\Models\Core\Asistencia::query()
        ->where('cliente_id', $cliente->id)
        ->where('origen', 'biotime')
        ->whereNull('fecha_hora_salida')
        ->exists())->toBeTrue();
});

it('isolates tokens per sucursal (multi-sede)', function () {
    Queue::fake();

    $sedeA = biotimeSucursal('sede-a', true);
    $sedeB = biotimeSucursal('sede-b', false);
    biotimeAgentSetting($sedeA, 'token-a');
    biotimeAgentSetting($sedeB, 'token-b');

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [
            ['id' => 10, 'dept_code' => 'A', 'dept_name' => 'Alpha'],
        ],
    ], ['Authorization' => 'Bearer token-a'])
        ->assertOk()
        ->assertJsonPath('sucursal_id', $sedeA->id);

    expect(BioTimeSucursalSetting::forSucursal($sedeA->id)->fresh()->last_received_at)->not->toBeNull()
        ->and(BioTimeSucursalSetting::forSucursal($sedeB->id)->fresh()->last_received_at)->toBeNull();

    $this->getJson('/api/biotime/health', ['Authorization' => 'Bearer token-b'])
        ->assertOk()
        ->assertJsonPath('sucursal_id', $sedeB->id);

    $settingB = BioTimeSucursalSetting::forSucursal($sedeB->id)->fresh();
    expect($settingB->last_heartbeat_at)->not->toBeNull()
        ->and($settingB->last_received_at)->toBeNull();
});

it('requires auth on health and updates heartbeat', function () {
    $sucursal = biotimeSucursal('health-sede');
    $setting = biotimeAgentSetting($sucursal, 'health-token');

    $this->getJson('/api/biotime/health')->assertUnauthorized();

    $this->getJson('/api/biotime/health', ['Authorization' => 'Bearer health-token'])
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('sucursal_id', $sucursal->id);

    expect($setting->fresh()->last_heartbeat_at)->not->toBeNull();
});

it('links BioTime employees to Cliente.numero_documento without creating missing clients', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        ...biotimeIdentity('C001'),
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
    ]);

    app(BioTimeSyncService::class)->process('employees', '2026-05-28 16:00:00', [
        ['id' => 10, 'emp_code' => 'C001', 'first_name' => 'Cliente'],
        ['id' => 11, 'emp_code' => 'NOEXISTE', 'first_name' => 'Sin Cliente'],
    ], (string) Str::uuid());

    expect($cliente->refresh()->biotime_id)->toBe(10)
        ->and(Cliente::query()->where('codigo', 'NOEXISTE')->exists())->toBeFalse()
        ->and(BioTimeSyncLog::query()->where('biotime_id', 11)->where('status', 'pending')->exists())->toBeTrue();
});

it('creates BioTime attendance when a transaction is linked to a client', function () {
    $sucursal = biotimeSucursal('tx-sede');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        ...biotimeIdentity('C002'),
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
    ]);
    BioTimeMapping::query()->create([
        'mapping_type' => 'department',
        'biotime_id' => 1,
        'target_type' => 'sucursal',
        'target_id' => $sucursal->id,
        'sucursal_id' => $sucursal->id,
    ]);

    \App\Models\BioTime\BioTimeDevice::query()->create([
        'biotime_id' => 501,
        'serial_number' => 'SN-TX-IN',
        'alias' => 'Entrada',
        'access_role' => \App\Models\BioTime\BioTimeDevice::ACCESS_ROLE_ENTRADA,
        'is_attendance' => true,
    ]);
    BioTimeMapping::query()->create([
        'mapping_type' => 'device',
        'biotime_id' => 501,
        'target_type' => 'sucursal',
        'target_id' => $sucursal->id,
        'sucursal_id' => $sucursal->id,
    ]);

    app(BioTimeSyncService::class)->process('transactions', '2026-05-28 16:00:00', [
        [
            'id' => 100,
            'emp_code' => 'C002',
            'department_id' => 1,
            'punch_time' => '2026-05-28 08:00:00',
            'punch_state' => '1',
            'terminal_sn' => 'SN-TX-IN',
        ],
    ], (string) Str::uuid());

    expect(BioTimeTransaction::query()->where('biotime_id', 100)->where('cliente_id', $cliente->id)->exists())->toBeTrue()
        ->and(Asistencia::query()->where('cliente_id', $cliente->id)->where('origen', 'biotime')->whereNull('fecha_hora_salida')->exists())->toBeTrue();
});

it('uses terminal access_role for entry/exit and ignores punch_state', function () {
    $sucursal = biotimeSucursal('role-sede');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        ...biotimeIdentity('ROLE-01'),
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
    ]);

    $deviceIn = \App\Models\BioTime\BioTimeDevice::query()->create([
        'biotime_id' => 601,
        'serial_number' => 'SN-IN',
        'alias' => 'In',
        'access_role' => 'entrada',
    ]);
    $deviceOut = \App\Models\BioTime\BioTimeDevice::query()->create([
        'biotime_id' => 602,
        'serial_number' => 'SN-OUT',
        'alias' => 'Out',
        'access_role' => 'salida',
    ]);
    foreach ([$deviceIn, $deviceOut] as $d) {
        BioTimeMapping::query()->create([
            'mapping_type' => 'device',
            'biotime_id' => $d->biotime_id,
            'target_type' => 'sucursal',
            'target_id' => $sucursal->id,
            'sucursal_id' => $sucursal->id,
        ]);
    }

    $svc = app(BioTimeSyncService::class);

    // Entrada aunque punch_state diga Check Out
    $svc->process('transactions', '2026-07-16 10:00:00', [[
        'id' => 2001,
        'emp_code' => 'ROLE-01',
        'punch_time' => '2026-07-16 10:00:00',
        'punch_state' => '1',
        'terminal_sn' => 'SN-IN',
    ]], (string) Str::uuid());

    $open = Asistencia::query()->where('cliente_id', $cliente->id)->whereNull('fecha_hora_salida')->first();
    expect($open)->not->toBeNull()
        ->and($open->sucursal_id)->toBe($sucursal->id);

    // Salida cierra
    $svc->process('transactions', '2026-07-16 11:00:00', [[
        'id' => 2002,
        'emp_code' => 'ROLE-01',
        'punch_time' => '2026-07-16 11:00:00',
        'punch_state' => '0',
        'terminal_sn' => 'SN-OUT',
    ]], (string) Str::uuid());

    expect($open->fresh()->fecha_hora_salida)->not->toBeNull()
        ->and(Asistencia::query()->where('cliente_id', $cliente->id)->whereNull('fecha_hora_salida')->count())->toBe(0);

    // Salida huérfana: se ignora
    $before = Asistencia::query()->where('cliente_id', $cliente->id)->count();
    $svc->process('transactions', '2026-07-16 12:00:00', [[
        'id' => 2003,
        'emp_code' => 'ROLE-01',
        'punch_time' => '2026-07-16 12:00:00',
        'punch_state' => '1',
        'terminal_sn' => 'SN-OUT',
    ]], (string) Str::uuid());

    expect(Asistencia::query()->where('cliente_id', $cliente->id)->count())->toBe($before)
        ->and(BioTimeTransaction::query()->where('biotime_id', 2003)->exists())->toBeTrue();
});

it('toggles entry/exit on terminal access_role ambos', function () {
    $sucursal = biotimeSucursal('ambos-sede');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        ...biotimeIdentity('AMB-01'),
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
    ]);

    \App\Models\BioTime\BioTimeDevice::query()->create([
        'biotime_id' => 701,
        'serial_number' => 'SN-BOTH',
        'access_role' => 'ambos',
    ]);

    $svc = app(BioTimeSyncService::class);
    $svc->process('transactions', '2026-07-16 10:00:00', [[
        'id' => 3001,
        'emp_code' => 'AMB-01',
        'punch_time' => '2026-07-16 10:00:00',
        'terminal_sn' => 'SN-BOTH',
    ]], (string) Str::uuid());

    $open = Asistencia::query()->where('cliente_id', $cliente->id)->whereNull('fecha_hora_salida')->first();
    expect($open)->not->toBeNull();

    $svc->process('transactions', '2026-07-16 11:00:00', [[
        'id' => 3002,
        'emp_code' => 'AMB-01',
        'punch_time' => '2026-07-16 11:00:00',
        'terminal_sn' => 'SN-BOTH',
    ]], (string) Str::uuid());

    expect($open->fresh()->fecha_hora_salida)->not->toBeNull();
});

it('skips asistencia when terminal has no access_role', function () {
    $sucursal = biotimeSucursal('norole-sede');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        ...biotimeIdentity('NOROLE-01'),
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
    ]);

    \App\Models\BioTime\BioTimeDevice::query()->create([
        'biotime_id' => 801,
        'serial_number' => 'SN-NONE',
        'access_role' => null,
    ]);

    app(BioTimeSyncService::class)->process('transactions', '2026-07-16 10:00:00', [[
        'id' => 4001,
        'emp_code' => 'NOROLE-01',
        'punch_time' => '2026-07-16 10:00:00',
        'terminal_sn' => 'SN-NONE',
        'punch_state' => '0',
    ]], (string) Str::uuid());

    expect(BioTimeTransaction::query()->where('biotime_id', 4001)->exists())->toBeTrue()
        ->and(Asistencia::query()->where('cliente_id', $cliente->id)->count())->toBe(0);
});

it('renders BioTime sedes tab and regenerates per-sucursal token', function () {
    $sucursal = biotimeSucursal('dash-sede');
    $user = biotimeAdmin($sucursal);
    biotimeAgentSetting($sucursal, 'old-token');

    $this->actingAs($user);
    session([\App\Services\SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    Livewire::test(BioTimeDashboard::class)
        ->assertSee('BioTime')
        ->call('setTab', 'security')
        ->assertSet('tab', 'sedes')
        ->assertSee('Configuracion BioTime por sede')
        ->assertSee($sucursal->nombre)
        ->call('regenerateSucursalToken', $sucursal->id)
        ->assertDispatched('show-flash');

    $secret = BioTimeSucursalSetting::forSucursal($sucursal->id)->fresh()->webhook_secret;
    expect($secret)->toStartWith('bt_')
        ->and($secret)->not->toBe('old-token');
});

it('saves sucursal BioTime settings from the sedes tab', function () {
    $sucursal = biotimeSucursal('save-sede');
    $user = biotimeAdmin($sucursal);

    $this->actingAs($user);
    session([\App\Services\SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    Livewire::test(BioTimeDashboard::class)
        ->call('setTab', 'sedes')
        ->set("settingForms.{$sucursal->id}.area_biotime_id", '2')
        ->set("settingForms.{$sucursal->id}.biotime_base_url", 'http://127.0.0.1:8085')
        ->set("settingForms.{$sucursal->id}.poll_interval_seconds", 900)
        ->set("settingForms.{$sucursal->id}.enabled", true)
        ->call('saveSucursalSetting', $sucursal->id)
        ->assertDispatched('show-flash');

    $setting = BioTimeSucursalSetting::forSucursal($sucursal->id)->fresh();
    expect($setting->area_biotime_id)->toBe(2)
        ->and($setting->biotime_base_url)->toBe('http://127.0.0.1:8085')
        ->and($setting->poll_interval_seconds)->toBe(900);
});

it('renders operational dashboard per sucursal and dispatches reconcile', function () {
    Queue::fake();

    $sucursal = biotimeSucursal('ops-sede');
    $user = biotimeAdmin($sucursal);
    $setting = biotimeAgentSetting($sucursal, 'ops-token');
    $setting->forceFill([
        'last_heartbeat_at' => now()->subHours(3),
        'last_received_at' => now()->subHour(),
        'enabled' => true,
    ])->save();

    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        ...biotimeIdentity('OPS-001'),
    ]);

    BioTimeAccessCommand::factory()->create([
        'sucursal_id' => $sucursal->id,
        'cliente_id' => $cliente->id,
        'emp_code' => 'OPS-001',
        'status' => BioTimeAccessCommand::STATUS_PENDING,
    ]);
    BioTimeAccessCommand::factory()->create([
        'sucursal_id' => $sucursal->id,
        'cliente_id' => $cliente->id,
        'emp_code' => 'OPS-001',
        'status' => BioTimeAccessCommand::STATUS_FAILED,
        'acked_at' => now()->subHours(2),
        'last_error' => 'timeout',
    ]);

    $this->actingAs($user);
    session([\App\Services\SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    Livewire::test(BioTimeDashboard::class)
        ->assertSee('Salud del puente')
        ->assertSee('Todas las sedes')
        ->assertSee($sucursal->nombre)
        ->assertSeeHtml('Aviso &gt; 2h')
        ->assertSee('Reconciliar acceso')
        ->call('setTab', 'sedes')
        ->assertSee('Pending 1')
        ->assertSee('Failed 24h 1')
        ->call('reconcileAccess', $sucursal->id)
        ->assertDispatched('show-flash');

    Queue::assertPushed(ReconcileBioTimeAccessForSucursal::class, function (ReconcileBioTimeAccessForSucursal $job) use ($sucursal): bool {
        return $job->sucursalId === $sucursal->id;
    });
});

it('links employees and transactions by numero_documento even when codigo differs', function () {
    $sucursal = biotimeSucursal('dni-link');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('44556677', '10042'),
    ]);

    app(BioTimeSyncService::class)->process('employees', '2026-05-28 16:00:00', [
        ['id' => 88, 'emp_code' => '44556677', 'first_name' => 'Dni'],
    ], (string) Str::uuid(), $sucursal->id);

    app(BioTimeSyncService::class)->process('transactions', '2026-05-28 16:00:00', [
        [
            'id' => 8801,
            'emp_code' => '44556677',
            'punch_time' => '2026-05-28 08:00:00',
        ],
    ], (string) Str::uuid(), $sucursal->id);

    expect($cliente->refresh()->biotime_id)->toBe(88)
        ->and(BioTimeTransaction::query()->where('emp_code', '44556677')->value('cliente_id'))->toBe($cliente->id);
});

it('does not link employees by cliente.codigo', function () {
    $sucursal = biotimeSucursal('legacy-code');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('44556678', '10043'),
    ]);

    app(BioTimeSyncService::class)->process('employees', '2026-05-28 16:00:00', [
        ['id' => 89, 'emp_code' => '10043', 'first_name' => 'Legacy'],
    ], (string) Str::uuid(), $sucursal->id);

    app(BioTimeSyncService::class)->process('transactions', '2026-05-28 16:00:00', [
        [
            'id' => 8901,
            'emp_code' => '10043',
            'punch_time' => '2026-05-28 08:05:00',
        ],
    ], (string) Str::uuid(), $sucursal->id);

    expect($cliente->refresh()->biotime_id)->toBeNull()
        ->and(BioTimeTransaction::query()->where('emp_code', '10043')->value('cliente_id'))->toBeNull();
});

it('counts heartbeat employee_codes as managed only by documento', function () {
    $sucursal = biotimeSucursal('hb-managed');
    biotimeAgentSetting($sucursal, 'hb-managed-token');
    $user = User::factory()->create();
    Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        ...biotimeIdentity('11223344', '10050'),
    ]);

    \App\Models\BioTime\BioTimeDevice::query()->create([
        'sucursal_id' => $sucursal->id,
        'biotime_id' => 1,
        'serial_number' => 'HB-1',
        'access_enabled' => true,
        'capacity_limit' => 500,
    ]);

    $this->postJson('/api/biotime/heartbeat', [
        'bridge_version' => 'test',
        'devices' => [[
            'biotime_id' => 1,
            'serial_number' => 'HB-1',
            'online' => true,
            'capacity' => 500,
            'employees_count' => 3,
            'employee_codes' => ['11223344', '10050', 'STAFF-99'],
            'inventory_at' => now()->toIso8601String(),
            'inventory_source' => 'terminal_counter',
        ]],
    ], ['Authorization' => 'Bearer hb-managed-token'])->assertOk();

    $device = \App\Models\BioTime\BioTimeDevice::query()
        ->where('sucursal_id', $sucursal->id)
        ->where('serial_number', 'HB-1')
        ->firstOrFail();

    // 11223344 es el documento (managed). 10050 (codigo interno) y STAFF-99 son protected.
    expect($device->protected_users_count)->toBe(2);
});
