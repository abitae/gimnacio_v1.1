<?php

use App\Livewire\POS\CustomerDebts;
use App\Models\Core\Caja;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\PaymentMethod;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('punto_venta.ver', 'web');
});

it('allows collecting a pending customer debt from the debts tray', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('punto_venta.ver');
    $this->actingAs($user);

    PaymentMethod::factory()->create([
        'nombre' => 'Efectivo',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $debt = ClientDebt::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => 'Pos',
        'origen_id' => 99,
        'monto_total' => 80,
        'monto_pagado' => 0,
        'saldo_pendiente' => 80,
        'fecha_registro' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(3)->toDateString(),
        'estado' => 'pendiente',
    ]);

    Livewire::actingAs($user)
        ->test(CustomerDebts::class)
        ->call('abrirModalCobro', $debt->id)
        ->assertSet('debtIdSeleccionada', $debt->id)
        ->call('procesarCobro');

    expect($debt->fresh()->estado)->toBe('pagado');
    expect((float) $debt->fresh()->saldo_pendiente)->toBe(0.0);
});
