<?php

declare(strict_types=1);

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeAccessCommandService;
use Illuminate\Support\Str;

function accessCommandSucursal(string $suffix = ''): Sucursal
{
    $codigo = 'ac-'.Str::lower(Str::random(6)).($suffix !== '' ? '-'.$suffix : '');

    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa Access '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal Access',
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

function accessCommandCliente(Sucursal $sucursal, User $user, ?string $codigo = null): Cliente
{
    return Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'codigo' => $codigo ?? 'C'.Str::upper(Str::random(8)),
    ]);
}

it('enqueues activate with desired area and emp_code = cliente.codigo', function () {
    $sucursal = accessCommandSucursal();
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'area_biotime_id' => 2,
    ])->save();

    $user = User::factory()->create();
    $cliente = accessCommandCliente($sucursal, $user, 'CLI-ACT-001');

    $command = app(BioTimeAccessCommandService::class)->enqueue(
        $sucursal,
        $cliente,
        BioTimeAccessCommand::ACTION_ACTIVATE
    );

    expect($command)->not->toBeNull()
        ->and($command->sucursal_id)->toBe($sucursal->id)
        ->and($command->cliente_id)->toBe($cliente->id)
        ->and($command->emp_code)->toBe('CLI-ACT-001')
        ->and($command->action)->toBe('activate')
        ->and($command->desired_area_biotime_id)->toBe(2)
        ->and($command->status)->toBe('pending')
        ->and($command->attempts)->toBe(0);
});

it('does not enqueue when cliente has no codigo', function () {
    $sucursal = accessCommandSucursal('nocod');
    BioTimeSucursalSetting::forSucursal($sucursal->id)->forceFill([
        'area_biotime_id' => 2,
    ])->save();

    $user = User::factory()->create();
    $cliente = Cliente::factory()->create([
        'sucursal_id' => $sucursal->id,
        'created_by' => $user->id,
        'codigo' => null,
    ]);

    $command = app(BioTimeAccessCommandService::class)->enqueue(
        $sucursal,
        $cliente,
        BioTimeAccessCommand::ACTION_ACTIVATE
    );

    expect($command)->toBeNull()
        ->and(BioTimeAccessCommand::query()->where('cliente_id', $cliente->id)->count())->toBe(0);
});

it('does not duplicate pending identical commands (idempotent enqueue)', function () {
    $sucursal = accessCommandSucursal('idem');
    $user = User::factory()->create();
    $cliente = accessCommandCliente($sucursal, $user);

    $service = app(BioTimeAccessCommandService::class);

    $first = $service->enqueue($sucursal->id, $cliente, BioTimeAccessCommand::ACTION_DEACTIVATE);
    $second = $service->enqueue($sucursal->id, $cliente, BioTimeAccessCommand::ACTION_DEACTIVATE);

    expect($first)->not->toBeNull()
        ->and($second->id)->toBe($first->id)
        ->and(BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursal->id)
            ->where('cliente_id', $cliente->id)
            ->where('action', 'deactivate')
            ->where('status', 'pending')
            ->count())->toBe(1);
});

it('allows a new pending after previous was acked', function () {
    $sucursal = accessCommandSucursal('acked');
    $user = User::factory()->create();
    $cliente = accessCommandCliente($sucursal, $user);

    $service = app(BioTimeAccessCommandService::class);
    $first = $service->enqueue($sucursal, $cliente, BioTimeAccessCommand::ACTION_ACTIVATE);
    $first->forceFill([
        'status' => BioTimeAccessCommand::STATUS_ACKED,
        'acked_at' => now(),
    ])->save();

    $second = $service->enqueue($sucursal, $cliente, BioTimeAccessCommand::ACTION_ACTIVATE);

    expect($second->id)->not->toBe($first->id)
        ->and($second->status)->toBe('pending')
        ->and($second->emp_code)->toBe($cliente->codigo);
});

it('creates via factory with emp_code = cliente.codigo', function () {
    $command = BioTimeAccessCommand::factory()->deactivate()->create();
    $cliente = Cliente::query()->findOrFail($command->cliente_id);

    expect($command->action)->toBe('deactivate')
        ->and($command->status)->toBe('pending')
        ->and($command->emp_code)->toBe((string) $cliente->codigo);
});
