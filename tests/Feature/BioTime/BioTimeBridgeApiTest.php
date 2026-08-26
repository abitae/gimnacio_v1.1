<?php

declare(strict_types=1);

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\BioTime\BioTimeAccessCommandService;
use Illuminate\Support\Carbon;

it('lists pending commands for the authenticated sucursal and marks them processing', function () {
    $sedeA = biotimeSucursal('cmd-a');
    $sedeB = biotimeSucursal('cmd-b', false);
    biotimeAgentSetting($sedeA, 'token-a');
    biotimeAgentSetting($sedeB, 'token-b');

    $user = User::factory()->create();
    $clienteA = Cliente::factory()->create([
        'sucursal_id' => $sedeA->id,
        'created_by' => $user->id,
        ...biotimeIdentity('CA-001'),
    ]);
    $clienteB = Cliente::factory()->create([
        'sucursal_id' => $sedeB->id,
        'created_by' => $user->id,
        ...biotimeIdentity('CB-001'),
    ]);

    $service = app(BioTimeAccessCommandService::class);
    $cmdA = $service->enqueue($sedeA, $clienteA, BioTimeAccessCommand::ACTION_ACTIVATE);
    $service->enqueue($sedeB, $clienteB, BioTimeAccessCommand::ACTION_DEACTIVATE);

    $response = $this->getJson('/api/biotime/commands?limit=50', [
        'Authorization' => 'Bearer token-a',
    ])->assertOk()
        ->assertJsonPath('sucursal_id', $sedeA->id)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $cmdA->id)
        ->assertJsonPath('data.0.emp_code', 'CA-001')
        ->assertJsonPath('data.0.emp_code_aliases', [])
        ->assertJsonPath('data.0.status', 'processing');

    expect($cmdA->fresh()->status)->toBe(BioTimeAccessCommand::STATUS_PROCESSING)
        ->and(BioTimeAccessCommand::query()->where('sucursal_id', $sedeB->id)->where('status', 'pending')->count())->toBe(1);

    expect($response->json('data.0.action'))->toBe('activate');
});

it('leases again a processing command whose lease expired or was never recorded', function () {
    $sede = biotimeSucursal('cmd-recover');
    biotimeAgentSetting($sede, 'recover-token');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sede->id,
        'created_by' => $user->id,
        ...biotimeIdentity('RECOVER-001'),
    ]);
    $command = app(BioTimeAccessCommandService::class)
        ->enqueue($sede, $cliente, BioTimeAccessCommand::ACTION_ACTIVATE);
    $command->forceFill([
        'status' => BioTimeAccessCommand::STATUS_PROCESSING,
        'leased_at' => now()->subMinutes(10),
        'lease_expires_at' => null,
        'attempts' => 1,
    ])->save();

    $this->getJson('/api/biotime/commands', [
        'Authorization' => 'Bearer recover-token',
    ])->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $command->id)
        ->assertJsonPath('data.0.idempotency_key', $command->idempotency_key);

    expect($command->fresh()->attempts)->toBe(2)
        ->and($command->fresh()->lease_expires_at)->not->toBeNull();
});

it('acks a command only for its own sucursal and updates heartbeat', function () {
    $sedeA = biotimeSucursal('ack-a');
    $sedeB = biotimeSucursal('ack-b', false);
    biotimeAgentSetting($sedeA, 'ack-token-a');
    biotimeAgentSetting($sedeB, 'ack-token-b');

    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sedeA->id,
        'created_by' => $user->id,
        ...biotimeIdentity('ACK-001'),
    ]);
    $command = app(BioTimeAccessCommandService::class)->enqueue($sedeA, $cliente, BioTimeAccessCommand::ACTION_ACTIVATE);

    $this->postJson("/api/biotime/commands/{$command->id}/ack", [
        'status' => 'acked',
    ], ['Authorization' => 'Bearer ack-token-b'])
        ->assertNotFound();

    $this->postJson("/api/biotime/commands/{$command->id}/ack", [
        'status' => 'acked',
        'biotime_id' => 4401,
    ], ['Authorization' => 'Bearer ack-token-a'])
        ->assertOk()
        ->assertJsonPath('command.status', 'acked');

    expect($command->fresh()->status)->toBe('acked')
        ->and($command->fresh()->acked_at)->not->toBeNull()
        ->and(BioTimeSucursalSetting::forSucursal($sedeA->id)->fresh()->last_heartbeat_at)->not->toBeNull()
        ->and($cliente->fresh()->biotime_id)->toBe(4401)
        ->and(\App\Models\BioTime\BioTimeEmployee::query()->where('biotime_id', 4401)->where('cliente_id', $cliente->id)->exists())->toBeTrue();
});

it('increments attempts on failed ack', function () {
    $sede = biotimeSucursal('fail-ack');
    biotimeAgentSetting($sede, 'fail-token');
    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sede->id,
        'created_by' => $user->id,
        ...biotimeIdentity('FAIL-001'),
    ]);
    $command = app(BioTimeAccessCommandService::class)->enqueue($sede, $cliente, BioTimeAccessCommand::ACTION_DEACTIVATE);

    $this->postJson("/api/biotime/commands/{$command->id}/ack", [
        'status' => 'failed',
        'error' => 'device offline',
    ], ['Authorization' => 'Bearer fail-token'])
        ->assertOk()
        ->assertJsonPath('command.status', 'failed')
        ->assertJsonPath('command.attempts', 1);

    expect($command->fresh()->last_error)->toBe('device offline');
});

it('builds roster with active flag from vigente matricula only', function () {
    Carbon::setTestNow(Carbon::parse('2026-07-15'));

    $sedeA = biotimeSucursal('roster-a');
    $sedeB = biotimeSucursal('roster-b', false);
    biotimeAgentSetting($sedeA, 'roster-a');
    biotimeAgentSetting($sedeB, 'roster-b');

    $user = User::factory()->create();
    $membresia = Membresia::factory()->create(['sucursal_id' => $sedeA->id]);

    $activo = Cliente::factory()->create([
        'sucursal_id' => $sedeA->id,
        'created_by' => $user->id,
        ...biotimeIdentity('ACTIVO-01'),
    ]);
    $vencido = Cliente::factory()->create([
        'sucursal_id' => $sedeA->id,
        'created_by' => $user->id,
        ...biotimeIdentity('VENCIDO-01'),
    ]);
    $otraSede = Cliente::factory()->create([
        'sucursal_id' => $sedeB->id,
        'created_by' => $user->id,
        ...biotimeIdentity('OTRA-01'),
    ]);
    $sinDocumento = Cliente::factory()->create([
        'sucursal_id' => $sedeA->id,
        'created_by' => $user->id,
        'codigo' => null,
        'numero_documento' => '   ',
    ]);

    ClienteMatricula::query()->create([
        'cliente_id' => $activo->id,
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
        'sucursal_id' => $sedeA->id,
    ]);

    ClienteMatricula::query()->create([
        'cliente_id' => $vencido->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => '2026-01-01',
        'fecha_inicio' => '2026-01-01',
        'fecha_fin' => '2026-06-01',
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
        'modalidad_pago' => 'contado',
        'requiere_plan_cuotas' => false,
        'cuota_inicial_monto' => 0,
        'asesor_id' => $user->id,
        'sucursal_id' => $sedeA->id,
    ]);

    $this->getJson('/api/biotime/roster', ['Authorization' => 'Bearer roster-a'])
        ->assertOk()
        ->assertJsonPath('sucursal_id', $sedeA->id)
        ->assertJsonFragment(['cliente_id' => $activo->id, 'emp_code' => 'ACTIVO-01', 'active' => true])
        ->assertJsonFragment(['cliente_id' => $vencido->id, 'emp_code' => 'VENCIDO-01', 'active' => false])
        ->assertJsonMissing(['cliente_id' => $otraSede->id])
        ->assertJsonMissing(['cliente_id' => $sinDocumento->id]);

    Carbon::setTestNow();
});

it('rejects bridge endpoints when setting is disabled', function () {
    $sede = biotimeSucursal('bridge-off');
    biotimeAgentSetting($sede, 'off-token', enabled: false);

    $this->getJson('/api/biotime/commands', ['Authorization' => 'Bearer off-token'])->assertForbidden();
    $this->getJson('/api/biotime/roster', ['Authorization' => 'Bearer off-token'])->assertForbidden();
});

it('lists bridge endpoints on health', function () {
    $sede = biotimeSucursal('health-ep');
    biotimeAgentSetting($sede, 'health-ep-token');

    $this->getJson('/api/biotime/health', ['Authorization' => 'Bearer health-ep-token'])
        ->assertOk()
        ->assertJsonFragment(['GET /api/biotime/commands'])
        ->assertJsonFragment(['POST /api/biotime/commands/{id}/ack'])
        ->assertJsonFragment(['GET /api/biotime/roster']);
});
