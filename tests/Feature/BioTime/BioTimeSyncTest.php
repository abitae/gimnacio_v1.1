<?php

use App\Jobs\BioTime\ProcessBioTimeDepartments;
use App\Livewire\BioTime\BioTimeDashboard;
use App\Models\BioTime\BioTimeMapping;
use App\Models\BioTime\BioTimeSetting;
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

function biotimeSucursal(): Sucursal
{
    $empresa = Empresa::query()->create(['nombre' => 'Empresa Test', 'estado' => 'activa']);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => 'principal',
        'nombre' => 'Sucursal Principal',
        'estado' => 'activa',
        'es_principal' => true,
    ]);
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
    BioTimeSetting::current()->forceFill(['webhook_secret' => 'valid-token'])->save();

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [],
    ], ['Authorization' => 'Bearer invalid'])->assertUnauthorized();
});

it('validates the received entity', function () {
    BioTimeSetting::current()->forceFill(['webhook_secret' => 'valid-token'])->save();

    $this->postJson('/api/biotime/sync', [
        'entity' => 'unknown',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [],
    ], ['Authorization' => 'Bearer valid-token'])->assertUnprocessable();
});

it('accepts a department batch and dispatches the specific job', function () {
    Queue::fake();
    BioTimeSetting::current()->forceFill(['webhook_secret' => 'valid-token'])->save();

    $this->postJson('/api/biotime/sync', [
        'entity' => 'departments',
        'timestamp' => '2026-05-28 16:00:00',
        'data' => [
            ['id' => 1, 'dept_code' => 'ADM', 'dept_name' => 'Administracion'],
        ],
    ], ['Authorization' => 'Bearer valid-token'])
        ->assertAccepted()
        ->assertJsonPath('queued', true);

    Queue::assertPushed(ProcessBioTimeDepartments::class);
    expect(BioTimeSyncBatch::query()->where('entity', 'departments')->exists())->toBeTrue();
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
    $sucursal = biotimeSucursal();
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

it('renders the BioTime dashboard and regenerates the token', function () {
    $sucursal = biotimeSucursal();
    $user = biotimeAdmin($sucursal);

    $this->actingAs($user);
    session([\App\Services\SucursalContext::SUCURSAL_ID_KEY => $sucursal->id]);

    Livewire::test(BioTimeDashboard::class)
        ->assertSee('BioTime')
        ->call('setTab', 'security')
        ->call('regenerateToken')
        ->assertDispatched('show-flash');

    expect(BioTimeSetting::activeSecret())->not()->toBeNull();
});
