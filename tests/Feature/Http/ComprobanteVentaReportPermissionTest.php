<?php

use App\Models\Core\Caja;
use App\Models\Core\Venta;
use App\Models\User;
use Spatie\Permission\Models\Permission;

it('allows report users to view sale receipts', function () {
    Permission::findOrCreate('reporte.ver', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo('reporte.ver');
    $this->actingAs($user);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);
    $venta = Venta::create([
        'numero_venta' => 'V-REPORT-001',
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'tipo_comprobante' => 'ticket',
        'serie_comprobante' => 'T001',
        'numero_comprobante' => '000010',
        'subtotal' => 10,
        'descuento' => 0,
        'igv' => 0,
        'total' => 10,
        'metodo_pago' => 'efectivo',
        'estado' => 'completada',
        'fecha_venta' => now(),
    ]);

    $this->get(route('ventas.comprobante', $venta))->assertOk();
});

it('blocks users without report or pos permission from sale receipts', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $caja = Caja::create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
    ]);
    $venta = Venta::create([
        'numero_venta' => 'V-BLOCK-001',
        'caja_id' => $caja->id,
        'usuario_id' => $user->id,
        'tipo_comprobante' => 'ticket',
        'serie_comprobante' => 'T001',
        'numero_comprobante' => '000011',
        'subtotal' => 10,
        'descuento' => 0,
        'igv' => 0,
        'total' => 10,
        'metodo_pago' => 'efectivo',
        'estado' => 'completada',
        'fecha_venta' => now(),
    ]);

    $this->get(route('ventas.comprobante', $venta))->assertForbidden();
});
