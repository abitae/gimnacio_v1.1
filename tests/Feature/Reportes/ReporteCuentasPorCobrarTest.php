<?php

use App\Livewire\Reportes\ReporteCuentasPorCobrarLive;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('reporte.ver', 'web');
    Permission::findOrCreate('punto_venta.ver', 'web');
});

it('renders analytic accounts receivable report read-only', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    ClientDebt::create([
        'cliente_id' => $cliente->id,
        'origen_tipo' => 'Pos',
        'origen_id' => 1,
        'monto_total' => 50,
        'monto_pagado' => 0,
        'saldo_pendiente' => 50,
        'fecha_registro' => now()->toDateString(),
        'estado' => 'pendiente',
    ]);

    Livewire::test(ReporteCuentasPorCobrarLive::class)
        ->assertSee('Cuentas por cobrar')
        ->assertSee($cliente->nombres)
        ->assertDontSee('procesarCobro');
});

it('denies analytic report without reporte.ver', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('reportes.cuentas-por-cobrar'))->assertForbidden();
});
