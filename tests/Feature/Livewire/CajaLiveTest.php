<?php

use App\Livewire\Cajas\CajaLive;
use App\Models\Core\Caja;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('caja.ver', 'web');
    Permission::findOrCreate('caja.crear', 'web');
    Permission::findOrCreate('caja.cerrar', 'web');
});

it('opens cash box from livewire component', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['caja.ver', 'caja.crear']);
    $this->actingAs($user);

    Livewire::test(CajaLive::class)
        ->set('formApertura.saldo_inicial', '150.00')
        ->set('formApertura.observaciones_apertura', 'Turno mañana')
        ->call('abrirCaja')
        ->assertHasNoErrors();

    $caja = Caja::query()->where('usuario_id', $user->id)->where('estado', 'abierta')->first();

    expect($caja)->not->toBeNull();
    expect((float) $caja->saldo_inicial)->toBe(150.0);
});

it('closes cash box from livewire component', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['caja.ver', 'caja.crear', 'caja.cerrar']);
    $this->actingAs($user);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    Livewire::test(CajaLive::class)
        ->call('abrirModalCierre', $caja->id)
        ->set('formCierre.saldo_contado', '100.00')
        ->call('cerrarCaja')
        ->assertHasNoErrors();

    expect($caja->fresh()->estado)->toBe('cerrada');
});

it('forbids caja component without permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(CajaLive::class)->assertForbidden();
});
