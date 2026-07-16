<?php

declare(strict_types=1);

use App\Jobs\BioTime\ProcessBioTimeDepartments;
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
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeSyncService;
use App\Support\PermissionCatalog;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

function biotimeSucursal(?string $codigo = null, bool $esPrincipal = true): Sucursal
{
    $codigo ??= 'bt-'.Str::lower(Str::random(8));

    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa Test '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal '.$codigo,
        'estado' => 'activa',
        'es_principal' => $esPrincipal,
    ]);
}

function biotimeAgentSetting(Sucursal $sucursal, string $secret = 'valid-token', bool $enabled = true): BioTimeSucursalSetting
{
    $setting = BioTimeSucursalSetting::forSucursal($sucursal->id);
    $setting->forceFill([
        'webhook_secret' => $secret,
        'enabled' => $enabled,
    ])->save();

    return $setting->fresh();
}

function biotimeAdmin(?Sucursal $sucursal = null): User
{
    $user = User::factory()->create();
    $role = Role::query()->firstOrCreate(['name' => PermissionCatalog::SUPER_ADMIN_ROLE_NAME, 'guard_name' => 'web']);
    $user->assignRole($role);

    if ($sucursal) {
        $user->sucursales()->attach($sucursal->id);
        $user->forceFill(['default_sucursal_id' => $sucursal->id])->save();
    }

    return $user;
}

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
        ->assertAccepted()
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

it('accepts a department batch and updates last_received_at for that sucursal', function () {
    Queue::fake();
    $sucursal = biotimeSucursal('sync-sede');
    $setting = biotimeAgentSetting($sucursal, 'valid-token');

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [
            ['id' => 1, 'dept_code' => 'ADM', 'dept_name' => 'Administracion'],
        ],
    ], ['Authorization' => 'Bearer valid-token'])
        ->assertAccepted()
        ->assertJsonPath('queued', true)
        ->assertJsonPath('sucursal_id', $sucursal->id);

    Queue::assertPushed(ProcessBioTimeDepartments::class);
    expect(BioTimeSyncBatch::query()->where('entity', 'departments')->exists())->toBeTrue();

    $setting->refresh();
    expect($setting->last_received_at)->not->toBeNull()
        ->and($setting->last_heartbeat_at)->not->toBeNull();
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
        'codigo' => 'EMP-INLINE-1',
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
        ->assertAccepted()
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

it('links BioTime employees to Cliente.codigo without creating missing clients', function () {
    $sucursal = biotimeSucursal();
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'codigo' => 'C001',
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
        'codigo' => 'C002',
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

    app(BioTimeSyncService::class)->process('transactions', '2026-05-28 16:00:00', [
        ['id' => 100, 'emp_code' => 'C002', 'department_id' => 1, 'punch_time' => '2026-05-28 08:00:00', 'punch_state' => '0'],
    ], (string) Str::uuid());

    expect(BioTimeTransaction::query()->where('biotime_id', 100)->where('cliente_id', $cliente->id)->exists())->toBeTrue()
        ->and(Asistencia::query()->where('cliente_id', $cliente->id)->where('origen', 'biotime')->exists())->toBeTrue();
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
        'codigo' => 'OPS-001',
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
