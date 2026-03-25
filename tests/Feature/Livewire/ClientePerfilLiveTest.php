<?php

use App\Livewire\Clientes\ClienteLive;
use App\Livewire\Clientes\ClientePerfilLive;
use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $guard = config('auth.defaults.guard');
    foreach (['clientes.view', 'clientes.create', 'clientes.update'] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => $guard]);
    }
});

it('redirige invitados del perfil de clientes al login', function () {
    $this->get(route('clientes.perfil.index'))->assertRedirect(route('login'));
});

it('responde 200 en perfil index con permiso clientes.view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $this->get(route('clientes.perfil.index'))->assertOk();
});

it('responde 200 en listado /clientes con permiso clientes.view', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $this->get(route('clientes.index'))->assertOk();
});

it('lanza autorización al abrir nuevo cliente sin permiso clientes.create', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    expect(fn () => Livewire::test(ClientePerfilLive::class)->call('openClienteCreateModal'))
        ->toThrow(AuthorizationException::class);
});

it('abre modal de nuevo cliente con permiso clientes.create', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['clientes.view', 'clientes.create']);
    $this->actingAs($user);

    Livewire::test(ClientePerfilLive::class)
        ->call('openClienteCreateModal')
        ->assertSet('clienteModalState.create', true);
});

it('renderiza el componente listado secundario', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    Livewire::test(ClienteLive::class)->assertOk();
});

it('selecciona cliente en perfil y fija la ficha', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('clientes.view');
    $this->actingAs($user);

    $cliente = Cliente::create([
        'tipo_documento' => 'DNI',
        'numero_documento' => '90000001',
        'nombres' => 'Ana',
        'apellidos' => 'Prueba',
        'estado_cliente' => 'activo',
        'created_by' => $user->id,
    ]);

    Livewire::test(ClientePerfilLive::class)
        ->call('selectCliente', $cliente->id)
        ->assertSet('selectedClienteId', $cliente->id);
});
