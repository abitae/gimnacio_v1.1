<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\User;
use App\Services\CajaService;
use App\Services\SucursalContext;
use Spatie\Permission\Models\Permission;

it('registers a manual income on an open cash box', function () {
    $sucursal = biotimeSucursal('caja-service-manual-ingreso');
    $owner = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $owner->sucursales()->attach($sucursal->id);
    Permission::findOrCreate('caja.editar', 'web');
    $owner->givePermissionTo('caja.editar');
    $this->actingAs($owner);
    app(SucursalContext::class)->activate($sucursal);

    $caja = Caja::create([
        'usuario_id' => $owner->id,
        'sucursal_id' => $sucursal->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $movimiento = app(CajaService::class)->registrarIngresoManual($caja->id, [
        'monto' => 35.50,
        'concepto' => 'Ingreso por ajuste',
        'observaciones' => 'Prueba',
    ]);

    expect($movimiento->tipo)->toBe('entrada');
    expect($movimiento->categoria)->toBe(CajaMovimiento::CATEGORIA_MANUAL_INGRESO);
    expect($movimiento->origen_modulo)->toBe(CajaMovimiento::ORIGEN_MANUAL);
});

it('allows a user with manual cash permission to move another users open cash box', function () {
    $sucursal = biotimeSucursal('caja-service-cross-caja');
    $owner = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $operator = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $owner->sucursales()->attach($sucursal->id);
    $operator->sucursales()->attach($sucursal->id);
    Permission::findOrCreate('caja.movimiento_manual', 'web');
    $operator->givePermissionTo('caja.movimiento_manual');
    $this->actingAs($operator);
    app(SucursalContext::class)->activate($sucursal);

    $caja = Caja::create([
        'usuario_id' => $owner->id,
        'sucursal_id' => $sucursal->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $movimiento = app(CajaService::class)->registrarSalidaManual($caja->id, [
        'monto' => 20,
        'concepto' => 'Compra menor',
        'observaciones' => 'Motivo de prueba',
    ]);

    expect($movimiento->tipo)->toBe('salida');
    expect($movimiento->categoria)->toBe(CajaMovimiento::CATEGORIA_MANUAL_SALIDA);
});

it('registers an apertura movement and closing arqueo difference', function () {
    $sucursal = biotimeSucursal('caja-service-apertura-cierre');
    $owner = User::factory()->create(['default_sucursal_id' => $sucursal->id]);
    $owner->sucursales()->attach($sucursal->id);
    Permission::findOrCreate('caja.editar', 'web');
    $owner->givePermissionTo('caja.editar');
    $this->actingAs($owner);
    app(SucursalContext::class)->activate($sucursal);

    $caja = app(CajaService::class)->abrirCaja([
        'saldo_inicial' => 120,
        'observaciones_apertura' => 'Inicio de turno',
    ]);

    $apertura = CajaMovimiento::query()
        ->where('caja_id', $caja->id)
        ->where('categoria', CajaMovimiento::CATEGORIA_APERTURA)
        ->first();

    expect($apertura)->not->toBeNull();
    expect((float) $apertura->monto)->toBe(120.0);

    app(CajaService::class)->registrarIngresoManual($caja->id, [
        'monto' => 30,
        'concepto' => 'Ingreso adicional',
        'observaciones' => 'Prueba',
    ]);

    $cerrada = app(CajaService::class)->cerrarCaja($caja->id, [
        'saldo_contado' => 145,
        'observaciones_cierre' => 'Arqueo final',
    ]);

    expect((float) $cerrada->saldo_final)->toBe(150.0);
    expect((float) $cerrada->saldo_contado_cierre)->toBe(145.0);
    expect((float) $cerrada->diferencia_cierre)->toBe(-5.0);
});
