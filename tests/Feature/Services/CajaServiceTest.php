<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\User;
use App\Services\CajaService;
use Spatie\Permission\Models\Permission;

it('registers a manual income on an open cash box', function () {
    $owner = User::factory()->create();
    $this->actingAs($owner);

    $caja = Caja::create([
        'usuario_id' => $owner->id,
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
    $owner = User::factory()->create();
    $operator = User::factory()->create();
    Permission::findOrCreate('cajas.movimientos-manuales', 'web');
    $operator->givePermissionTo('cajas.movimientos-manuales');
    $this->actingAs($operator);

    $caja = Caja::create([
        'usuario_id' => $owner->id,
        'saldo_inicial' => 100,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);

    $movimiento = app(CajaService::class)->registrarSalidaManual($caja->id, [
        'monto' => 20,
        'concepto' => 'Compra menor',
    ]);

    expect($movimiento->tipo)->toBe('salida');
    expect($movimiento->categoria)->toBe(CajaMovimiento::CATEGORIA_MANUAL_SALIDA);
});
