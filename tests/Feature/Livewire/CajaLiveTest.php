<?php

use App\Livewire\Cajas\CajaLive;
use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\Pago;
use App\Models\Core\Venta;
use App\Models\User;
use App\Services\SucursalContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::findOrCreate('caja.ver', 'web');
    Permission::findOrCreate('caja.crear', 'web');
    Permission::findOrCreate('caja.cerrar', 'web');
});

it('opens cash box from livewire component', function () {
    $sucursal = biotimeSucursal('caja-live-abrir');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo(['caja.ver', 'caja.crear']);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

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
    $sucursal = biotimeSucursal('caja-live-cerrar');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo(['caja.ver', 'caja.crear', 'caja.cerrar']);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
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

it('muestra totales por tipo y metodo de pago solo de la caja abierta del usuario', function () {
    $sucursal = biotimeSucursal('caja-live-matriz');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $otro = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $otro->sucursales()->attach($sucursal->id);
    $user->givePermissionTo('caja.ver');
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $cajaAjena = Caja::create([
        'usuario_id' => $otro->id,
        'sucursal_id' => $sucursal->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $pagoMembresia = Pago::create([
        'cliente_id' => $cliente->id,
        'monto' => 100,
        'fecha_pago' => now(),
        'metodo_pago' => 'Efectivo',
        'registrado_por' => $user->id,
    ]);

    $venta = Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-CAJA-1',
        'fecha_venta' => now(),
        'subtotal' => 40,
        'descuento' => 0,
        'total' => 40,
        'monto_pagado' => 40,
        'estado' => 'completada',
        'metodo_pago' => 'Efectivo',
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_MEMBRESIA,
        'origen_modulo' => CajaMovimiento::ORIGEN_CLIENTE_MEMBRESIAS,
        'concepto' => 'Cobro membresía',
        'monto' => 100,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Pago::class,
        'referencia_id' => $pagoMembresia->id,
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_POS,
        'origen_modulo' => CajaMovimiento::ORIGEN_VENTAS,
        'concepto' => 'Venta POS',
        'monto' => 40,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Venta::class,
        'referencia_id' => $venta->id,
    ]);

    CajaMovimiento::create([
        'caja_id' => $cajaAjena->id,
        'usuario_id' => $otro->id,
        'sucursal_id' => $sucursal->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_MEMBRESIA,
        'origen_modulo' => CajaMovimiento::ORIGEN_CLIENTE_MEMBRESIAS,
        'concepto' => 'Cobro ajeno',
        'monto' => 999,
        'fecha_movimiento' => now(),
    ]);

    $component = Livewire::test(CajaLive::class)
        ->assertSee('Totales por tipo y método de pago');

    $matriz = $component->viewData('resumenCaja')['matriz_tipo_metodo'] ?? [];

    expect($component->viewData('cajaActiva')?->id)->toBe($caja->id)
        ->and($matriz['tipos'])->toBe(['Membresías', 'Venta POS'])
        ->and((float) $matriz['total_general'])->toBe(140.0)
        ->and((float) ($matriz['celdas']['Membresías']['Efectivo']['total'] ?? 0))->toBe(100.0)
        ->and((float) ($matriz['celdas']['Venta POS']['Efectivo']['total'] ?? 0))->toBe(40.0);
});

it('abre el modal de detalle de la matriz en caja con solo las operaciones de la celda', function () {
    $sucursal = biotimeSucursal('caja-live-matriz-detalle');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo('caja.ver');
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $pagoMembresia = Pago::create([
        'cliente_id' => $cliente->id,
        'monto' => 100,
        'fecha_pago' => now(),
        'metodo_pago' => 'Efectivo',
        'registrado_por' => $user->id,
    ]);

    $venta = Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-CAJA-DET',
        'fecha_venta' => now(),
        'subtotal' => 40,
        'descuento' => 0,
        'total' => 40,
        'monto_pagado' => 40,
        'estado' => 'completada',
        'metodo_pago' => 'Efectivo',
    ]);

    \App\Models\Core\VentaItem::create([
        'venta_id' => $venta->id,
        'tipo_item' => 'producto',
        'item_id' => 1,
        'nombre_item' => 'Proteína 1kg',
        'cantidad' => 1,
        'precio_unitario' => 25,
        'descuento' => 0,
        'subtotal' => 25,
    ]);

    \App\Models\Core\VentaItem::create([
        'venta_id' => $venta->id,
        'tipo_item' => 'producto',
        'item_id' => 2,
        'nombre_item' => 'Shaker',
        'cantidad' => 1,
        'precio_unitario' => 15,
        'descuento' => 0,
        'subtotal' => 15,
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_MEMBRESIA,
        'origen_modulo' => CajaMovimiento::ORIGEN_CLIENTE_MEMBRESIAS,
        'concepto' => 'Cobro membresía caja detalle',
        'monto' => 100,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Pago::class,
        'referencia_id' => $pagoMembresia->id,
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_POS,
        'origen_modulo' => CajaMovimiento::ORIGEN_VENTAS,
        'concepto' => 'Venta POS caja detalle',
        'monto' => 40,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Venta::class,
        'referencia_id' => $venta->id,
    ]);

    $component = Livewire::test(CajaLive::class)
        ->call('abrirDetalleMatriz', 'Membresías', 'Efectivo')
        ->assertSet('mostrarModalMatrizDetalle', true)
        ->assertSet('matrizDetalleTipo', 'Membresías')
        ->assertSet('matrizDetalleMetodo', 'Efectivo')
        ->assertSee('Detalle de operaciones')
        ->assertSee('Membresías · Efectivo');

    $movimientos = $component->viewData('movimientosMatrizDetalle');

    expect($movimientos->total())->toBe(1)
        ->and(collect($movimientos->items())->pluck('concepto')->all())
        ->toBe(['Cobro membresía caja detalle']);

    $component
        ->call('abrirDetalleMatriz', 'Venta POS', 'Efectivo')
        ->assertSet('matrizDetalleTipo', 'Venta POS')
        ->assertSee('Proteína 1kg')
        ->assertSee('Shaker');

    $posItems = collect($component->viewData('movimientosMatrizDetalle')->items())->first();
    expect($posItems['detalle_items'] ?? [])->toHaveCount(2)
        ->and(collect($posItems['detalle_items'])->pluck('nombre')->all())
        ->toBe(['Proteína 1kg', 'Shaker']);

    $component
        ->call('cerrarDetalleMatriz')
        ->assertSet('mostrarModalMatrizDetalle', false)
        ->assertSet('matrizDetalleTipo', null)
        ->assertSet('matrizDetalleMetodo', null);
});
