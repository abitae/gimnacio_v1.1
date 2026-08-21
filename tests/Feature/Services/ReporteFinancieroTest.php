<?php

use App\Livewire\Reportes\ReporteFinancieroLive;
use App\Models\Core\Caja;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Membresia;
use App\Models\Core\Pago;
use App\Models\Core\Venta;
use App\Models\User;
use App\Services\ReporteModuloService;
use App\Services\SucursalContext;
use Carbon\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

it('desglosa el reporte financiero por tipo y metodo sin incluir credito', function () {
    $sucursal = biotimeSucursal('fin-report');
    $user = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $user->sucursales()->attach($sucursal->id);
    $this->actingAs($user);
    app(SucursalContext::class)->activate($sucursal);

    $cliente = Cliente::factory()->create(['created_by' => $user->id]);
    $membresia = Membresia::create([
        'nombre' => 'Plan mensual',
        'duracion_dias' => 30,
        'precio_base' => 100,
        'estado' => 'activa',
    ]);
    $matricula = ClienteMatricula::create([
        'cliente_id' => $cliente->id,
        'tipo' => 'membresia',
        'membresia_id' => $membresia->id,
        'fecha_matricula' => now()->toDateString(),
        'fecha_inicio' => now()->toDateString(),
        'fecha_fin' => now()->addMonth()->toDateString(),
        'estado' => 'activa',
        'precio_lista' => 100,
        'descuento_monto' => 0,
        'precio_final' => 100,
    ]);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'sucursal_id' => $sucursal->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    Pago::create([
        'cliente_id' => $cliente->id,
        'cliente_matricula_id' => $matricula->id,
        'monto' => 100,
        'fecha_pago' => now(),
        'metodo_pago' => 'Efectivo',
        'registrado_por' => $user->id,
    ]);

    Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-FIN-1',
        'fecha_venta' => now(),
        'subtotal' => 40,
        'descuento' => 0,
        'total' => 40,
        'monto_pagado' => 40,
        'estado' => 'completada',
        'metodo_pago' => 'Efectivo',
        'es_credito' => false,
    ]);

    Venta::create([
        'caja_id' => $caja->id,
        'cliente_id' => $cliente->id,
        'usuario_id' => $user->id,
        'numero_venta' => 'V-FIN-CRED',
        'fecha_venta' => now(),
        'subtotal' => 200,
        'descuento' => 0,
        'total' => 200,
        'monto_pagado' => 0,
        'monto_inicial' => 0,
        'estado' => 'completada',
        'metodo_pago' => 'Crédito',
        'es_credito' => true,
    ]);

    $data = app(ReporteModuloService::class)->datosReporteFinanciero(
        now()->startOfDay()->format('Y-m-d\TH:i'),
        now()->setTime(23, 59)->format('Y-m-d\TH:i'),
    );

    $matriz = $data['resumen']['matriz_tipo_metodo'];

    expect((float) $data['resumen']['total_pagos'])->toBe(100.0)
        ->and((float) $data['resumen']['ingresos_totales'])->toBe(140.0)
        ->and((float) ($matriz['celdas']['Membresías']['Efectivo']['total'] ?? 0))->toBe(100.0)
        ->and((float) ($matriz['celdas']['Venta POS']['Efectivo']['total'] ?? 0))->toBe(40.0)
        ->and($matriz['celdas']['Venta POS']['Crédito'] ?? null)->toBeNull();
});

it('inicia el reporte financiero desde el inicio de mes a las 00:00 hasta hoy 23:59', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-20 19:20:00'));
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    Livewire::test(ReporteFinancieroLive::class)
        ->assertSet('fechaDesde', '2026-08-01T00:00')
        ->assertSet('fechaHasta', '2026-08-20T23:59')
        ->assertSee('Totales por tipo y método de pago')
        ->assertSee('Ingresos reales');

    Carbon::setTestNow();
});
