<?php

use App\Livewire\Reportes\ReporteCajasLive;
use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Cliente;
use App\Models\Core\Pago;
use App\Models\Core\Venta;
use App\Models\User;
use App\Services\CajaService;
use App\Services\ReporteModuloService;
use App\Services\SucursalContext;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('breaks down cash report payment totals by operation type', function () {
    $sucursal = biotimeSucursal('cash-report-breakdown');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
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
        'numero_venta' => 'V-TEST-1',
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
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_POS,
        'origen_modulo' => CajaMovimiento::ORIGEN_VENTAS,
        'concepto' => 'Venta POS',
        'monto' => 40,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Venta::class,
        'referencia_id' => $venta->id,
    ]);

    $data = app(ReporteModuloService::class)->datosReporteCajas(
        now()->toDateString(),
        now()->toDateString(),
    );

    $efectivo = $data['resumen']['por_metodo_pago']['Efectivo'] ?? null;

    expect($efectivo)->not->toBeNull()
        ->and((float) $efectivo['total'])->toBe(140.0)
        ->and((float) $efectivo['por_tipo']['Membresías']['total'])->toBe(100.0)
        ->and((float) $efectivo['por_tipo']['Venta POS']['total'])->toBe(40.0);

    $movPago = collect($data['detalle_movimientos'])->firstWhere('ticket_pago_id', $pagoMembresia->id);
    $movVenta = collect($data['detalle_movimientos'])->firstWhere('ticket_venta_id', $venta->id);

    expect($movPago)->not->toBeNull()
        ->and($movVenta)->not->toBeNull();
});

it('consolidates legacy cash names and distinguishes their operation origins', function () {
    $sucursal = biotimeSucursal('cash-report');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now()->subMinute(),
        'estado' => 'abierta',
    ]);

    $pagoMembresia = Pago::create([
        'cliente_id' => $cliente->id,
        'monto' => 100,
        'fecha_pago' => now(),
        'metodo_pago' => 'efectivo',
        'registrado_por' => $user->id,
    ]);
    $venta = Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-CASH-NORMALIZED',
        'fecha_venta' => now(),
        'subtotal' => 40,
        'descuento' => 0,
        'total' => 40,
        'monto_pagado' => 40,
        'estado' => 'completada',
        'metodo_pago' => 'Efectivo',
    ]);

    foreach ([
        [$pagoMembresia, CajaMovimiento::CATEGORIA_MEMBRESIA, 'Cobro membresía', 100],
        [$venta, CajaMovimiento::CATEGORIA_POS, 'Venta POS', 40],
    ] as [$referencia, $categoria, $concepto, $monto]) {
        CajaMovimiento::create([
            'caja_id' => $caja->id,
            'usuario_id' => $user->id,
            'tipo' => 'entrada',
            'categoria' => $categoria,
            'origen_modulo' => $referencia instanceof Pago
                ? CajaMovimiento::ORIGEN_CLIENTE_MEMBRESIAS
                : CajaMovimiento::ORIGEN_VENTAS,
            'concepto' => $concepto,
            'monto' => $monto,
            'fecha_movimiento' => now(),
            'referencia_tipo' => $referencia::class,
            'referencia_id' => $referencia->id,
        ]);
    }

    $resumen = app(CajaService::class)->obtenerResumenCaja($caja);
    $movimientos = collect($resumen['movimientos']);
    $matriz = $resumen['matriz_tipo_metodo'] ?? [];

    expect($movimientos->pluck('metodo_pago')->unique()->values()->all())->toBe(['Efectivo'])
        ->and((float) ($matriz['total_general'] ?? 0))->toBe(140.0)
        ->and((float) ($matriz['celdas']['Membresías']['Efectivo']['total'] ?? 0))->toBe(100.0)
        ->and((float) ($matriz['celdas']['Venta POS']['Efectivo']['total'] ?? 0))->toBe(40.0);
});

it('excludes credit sale debt from cash totals and lists credit sales separately', function () {
    $sucursal = biotimeSucursal('cash-report-credit');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $ventaCredito = Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-CRED-1',
        'fecha_venta' => now(),
        'subtotal' => 200,
        'descuento' => 0,
        'total' => 200,
        'monto_pagado' => 50,
        'estado' => 'completada',
        'metodo_pago' => 'Efectivo',
        'es_credito' => true,
        'monto_inicial' => 50,
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_POS,
        'origen_modulo' => CajaMovimiento::ORIGEN_VENTAS,
        'concepto' => 'Venta POS - V-CRED-1 (anticipo a credito)',
        'monto' => 50,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Venta::class,
        'referencia_id' => $ventaCredito->id,
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_POS,
        'origen_modulo' => CajaMovimiento::ORIGEN_VENTAS,
        'concepto' => 'Venta POS - V-CRED-1 legacy',
        'monto' => 200,
        'fecha_movimiento' => now()->addMinute(),
        'referencia_tipo' => Venta::class,
        'referencia_id' => $ventaCredito->id,
    ]);

    $data = app(ReporteModuloService::class)->datosReporteCajas(
        now()->toDateString(),
        now()->toDateString(),
    );

    expect((float) $data['resumen']['total_ingresos'])->toBe(50.0)
        ->and($data['ventas_credito'])->toHaveCount(1)
        ->and((float) $data['ventas_credito'][0]['saldo_pendiente'])->toBe(150.0)
        ->and((float) $data['resumen']['ventas_credito']['total_saldo_pendiente'])->toBe(150.0);

    $matriz = $data['resumen']['matriz_tipo_metodo'];
    expect((float) $matriz['total_general'])->toBe(50.0);
});

it('allows printing membership payment detail from cash report movements', function () {
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    Livewire::test(ReporteCajasLive::class)
        ->call('abrirTicketPago', 55)
        ->assertSet('mostrarModalTicketPago', true)
        ->assertSet('pagoIdTicketReporte', 55)
        ->call('cerrarTicketPago')
        ->assertSet('mostrarModalTicketPago', false)
        ->assertSet('pagoIdTicketReporte', null);
});

it('inicia el reporte de cajas en el dia actual de 00:00 a 23:59', function () {
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    Livewire::test(ReporteCajasLive::class)
        ->assertSet('fechaDesde', now()->startOfDay()->format('Y-m-d\TH:i'))
        ->assertSet('fechaHasta', now()->setTime(23, 59)->format('Y-m-d\TH:i'));
});

it('incluye solo cajas abiertas entre las 00:00 y las 23:59 del rango', function () {
    $sucursal = biotimeSucursal('cash-report-day-range');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $hoy = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now()->setTime(8, 30),
        'estado' => 'abierta',
    ]);

    Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now()->subDay()->setTime(10, 0),
        'estado' => 'abierta',
    ]);

    $data = app(ReporteModuloService::class)->datosReporteCajas(
        now()->startOfDay()->format('Y-m-d\TH:i'),
        now()->setTime(23, 59)->format('Y-m-d\TH:i'),
    );

    expect($data['resumen']['cantidad'])->toBe(1)
        ->and($data['cajas']->first()->id)->toBe($hoy->id);
});

it('pagina todas las tablas del reporte de cajas', function () {
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    Livewire::test(ReporteCajasLive::class)
        ->assertOk()
        ->assertSet('perPageCajas', 10)
        ->assertSet('perPageMovimientos', 15)
        ->assertSet('perPageUsuarios', 10)
        ->assertSet('perPageVentasCredito', 10)
        ->assertSet('perPageMatriz', 10)
        ->assertSee('Totales por tipo y método de pago')
        ->assertSee('Resumen por usuario de caja')
        ->assertSee('Ventas al crédito')
        ->assertSee('Detalle de movimientos')
        ->set('perPageCajas', 25)
        ->assertSet('perPageCajas', 25);
});

it('abre el modal de detalle de la matriz con solo las operaciones de la celda', function () {
    $sucursal = biotimeSucursal('cash-report-matriz-detalle');
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo('reporte.ver');
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
        'sucursal_id' => $sucursal->id,
    ]);

    $venta = Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-MATRIZ-DET',
        'fecha_venta' => now(),
        'subtotal' => 40,
        'descuento' => 0,
        'total' => 40,
        'monto_pagado' => 40,
        'estado' => 'completada',
        'metodo_pago' => 'Efectivo',
        'sucursal_id' => $sucursal->id,
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
        'sucursal_id' => $sucursal->id,
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
        'sucursal_id' => $sucursal->id,
    ]);

    CajaMovimiento::create([
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'tipo' => 'entrada',
        'categoria' => CajaMovimiento::CATEGORIA_MEMBRESIA,
        'origen_modulo' => CajaMovimiento::ORIGEN_CLIENTE_MEMBRESIAS,
        'concepto' => 'Cobro membresía matriz detalle',
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
        'concepto' => 'Venta POS matriz detalle',
        'monto' => 40,
        'fecha_movimiento' => now(),
        'referencia_tipo' => Venta::class,
        'referencia_id' => $venta->id,
    ]);

    $component = Livewire::test(ReporteCajasLive::class)
        ->call('abrirDetalleMatriz', 'Membresías', 'Efectivo')
        ->assertSet('mostrarModalMatrizDetalle', true)
        ->assertSet('matrizDetalleTipo', 'Membresías')
        ->assertSet('matrizDetalleMetodo', 'Efectivo')
        ->assertSee('Detalle de operaciones')
        ->assertSee('Membresías · Efectivo');

    $movimientos = $component->viewData('movimientosMatrizDetalle');

    expect($movimientos->total())->toBe(1)
        ->and(collect($movimientos->items())->pluck('concepto')->all())
        ->toBe(['Cobro membresía matriz detalle']);

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

it('abre y cierra el modal de previsualizacion pdf de la matriz de cajas', function () {
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    Livewire::test(ReporteCajasLive::class)
        ->call('abrirPreviewMatrizPdf')
        ->assertSet('mostrarModalMatrizPdf', true)
        ->assertSee('Descargar PDF')
        ->call('cerrarPreviewMatrizPdf')
        ->assertSet('mostrarModalMatrizPdf', false);
});

it('exporta a pdf la matriz de tipo y metodo de pago', function () {
    $sucursal = biotimeSucursal('cash-report-matriz-pdf');
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $response = $this->get(route('reportes.cajas.exportar.pdf', [
        'seccion' => 'matriz',
        'inline' => 1,
        'fecha_desde' => now()->toDateString(),
        'fecha_hasta' => now()->toDateString(),
    ]));

    $response->assertOk()
        ->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('inline')
        ->and($response->headers->get('content-disposition'))->toContain('totales_metodo_pago');
});
