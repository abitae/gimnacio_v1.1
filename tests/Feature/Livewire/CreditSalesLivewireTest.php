<?php

use App\Livewire\POS\CreditSales;
use App\Models\Core\Caja;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\Venta;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('punto_venta.ver', 'web');
});

it('shows action shortcuts for a credit sale with debt', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('punto_venta.ver');

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $venta = Venta::create([
        'numero_venta' => 'V-20260410-0001',
        'cliente_id' => $cliente->id,
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'tipo_comprobante' => 'ticket',
        'numero_comprobante' => '000001',
        'serie_comprobante' => 'T001',
        'subtotal' => 100,
        'descuento' => 0,
        'igv' => 15.25,
        'total' => 100,
        'metodo_pago' => 'Crédito',
        'es_credito' => true,
        'monto_inicial' => 20,
        'fecha_vencimiento_deuda' => now()->addDays(10)->toDateString(),
        'estado' => 'completada',
        'fecha_venta' => now(),
    ]);

    ClientDebt::create([
        'cliente_id' => $cliente->id,
        'venta_id' => $venta->id,
        'origen_tipo' => 'Pos',
        'origen_id' => $venta->id,
        'monto_total' => 100,
        'monto_pagado' => 20,
        'saldo_pendiente' => 80,
        'fecha_registro' => now()->toDateString(),
        'fecha_vencimiento' => now()->addDays(10)->toDateString(),
        'estado' => 'parcial',
    ]);

    Livewire::actingAs($user)
        ->test(CreditSales::class)
        ->assertSee(trim($cliente->nombres.' '.$cliente->apellidos))
        ->assertSee('Cliente gimnasio')
        ->assertSee('Pagar deuda del cliente')
        ->assertSee('Pagar esta venta');
});

it('shows pos-only customer data on credit sales', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('punto_venta.ver');

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    Venta::create([
        'numero_venta' => 'V-20260410-0002',
        'cliente_venta_nombre' => 'Cliente Externo Demo',
        'cliente_venta_documento' => '87654321',
        'cliente_venta_telefono' => '999888777',
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'tipo_comprobante' => 'ticket',
        'numero_comprobante' => '000002',
        'serie_comprobante' => 'T001',
        'subtotal' => 100,
        'descuento' => 0,
        'igv' => 15.25,
        'total' => 100,
        'metodo_pago' => 'Credito',
        'es_credito' => true,
        'monto_inicial' => 20,
        'fecha_vencimiento_deuda' => now()->addDays(10)->toDateString(),
        'estado' => 'completada',
        'fecha_venta' => now(),
    ]);

    Livewire::actingAs($user)
        ->test(CreditSales::class)
        ->assertSee('Cliente Externo Demo')
        ->assertSee('87654321')
        ->assertSee('999888777')
        ->assertSee('Cliente POS')
        ->set('search', '87654321')
        ->assertSee('Cliente Externo Demo');
});
