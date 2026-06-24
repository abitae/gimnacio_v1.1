<?php

use App\Livewire\Checking\CheckingLive;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\User;
use App\Services\DailyOperationsDebtService;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    foreach (['checking.ver', 'checking.crear', 'checking.editar'] as $perm) {
        Permission::findOrCreate($perm, 'web');
    }
});

it('loads debt summary when selecting a client in checking', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['checking.ver', 'checking.crear']);
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::factory()->create(['estado' => 'activa', 'precio_base' => 80]);

    ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->subDays(5)->toDateString(),
        'fecha_fin' => now()->addDays(25)->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 80,
        'descuento_monto' => 0,
        'precio_final' => 80,
    ]);

    ClientDebt::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => 'Pos',
        'origen_id' => 1,
        'monto_total' => 30,
        'monto_pagado' => 0,
        'saldo_pendiente' => 30,
        'fecha_registro' => now()->toDateString(),
        'fecha_vencimiento' => now()->subDay()->toDateString(),
        'estado' => 'vencido',
    ]);

    $expected = app(DailyOperationsDebtService::class)->summarizeCliente($cliente->id);

    Livewire::test(CheckingLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSet('selectedClienteId', $cliente->id)
        ->assertSet('debtSummary.total_pendiente', $expected['total_pendiente'])
        ->assertSet('debtSummary.tiene_deuda_vencida', true);
});

it('forbids checking component mount without permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CheckingLive::class)->assertForbidden();
});
