<?php

use App\Models\Core\Caja;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Producto;
use App\Models\Core\Venta;
use App\Models\Core\VentaItem;
use App\Models\Core\VentaPago;
use App\Models\User;
use App\Services\ReporteModuloService;

it('groups sold products by cash box and seller with detail rows', function () {
    $seller = User::factory()->create(['name' => 'Vendedor Uno']);
    $cashier = User::factory()->create(['name' => 'Cajero Uno']);
    $caja = Caja::create([
        'usuario_id' => $cashier->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now()->subHour(),
        'estado' => 'abierta',
    ]);
    $paymentMethod = PaymentMethod::create([
        'nombre' => 'Efectivo',
        'requiere_numero_operacion' => false,
        'requiere_entidad' => false,
        'estado' => 'activo',
    ]);
    $producto = Producto::factory()->create(['nombre' => 'Proteina Test']);

    $venta = Venta::create([
        'numero_venta' => 'V-TEST-001',
        'caja_id' => $caja->id,
        'usuario_id' => $seller->id,
        'tipo_comprobante' => 'ticket',
        'serie_comprobante' => 'T001',
        'numero_comprobante' => '000001',
        'subtotal' => 80,
        'descuento' => 0,
        'igv' => 0,
        'total' => 80,
        'metodo_pago' => 'efectivo',
        'payment_method_id' => $paymentMethod->id,
        'estado' => 'completada',
        'fecha_venta' => '2026-05-27 10:00:00',
    ]);
    VentaItem::create([
        'venta_id' => $venta->id,
        'tipo_item' => 'producto',
        'item_id' => $producto->id,
        'nombre_item' => $producto->nombre,
        'cantidad' => 2,
        'precio_unitario' => 40,
        'descuento' => 0,
        'subtotal' => 80,
    ]);
    VentaPago::create([
        'venta_id' => $venta->id,
        'payment_method_id' => $paymentMethod->id,
        'monto' => 80,
        'metodo_pago' => 'efectivo',
        'pagado_en' => '2026-05-27 10:00:00',
        'usuario_id' => $seller->id,
        'caja_id' => $caja->id,
    ]);

    $data = app(ReporteModuloService::class)->datosReporteProductosServicios('2026-05-27', '2026-05-27');

    expect($data['resumen']['productos_vendidos'])->toBe(2)
        ->and($data['resumen']['ventas_con_productos'])->toBe(1)
        ->and((float) $data['resumen']['total_productos_vendidos'])->toBe(80.0);

    $porCaja = $data['productos_por_caja']->first();
    expect($porCaja['caja'])->toBe('#'.$caja->id)
        ->and($porCaja['usuario_caja'])->toBe('Cajero Uno')
        ->and($porCaja['cantidad_productos'])->toBe(2)
        ->and($porCaja['ventas_count'])->toBe(1)
        ->and((float) $porCaja['total'])->toBe(80.0);

    $porUsuario = $data['productos_por_usuario']->first();
    expect($porUsuario['usuario'])->toBe('Vendedor Uno')
        ->and($porUsuario['cantidad_productos'])->toBe(2)
        ->and($porUsuario['ventas_count'])->toBe(1);

    $detalle = $data['detalle_productos_vendidos']->first();
    expect($detalle['venta_id'])->toBe($venta->id)
        ->and($detalle['producto'])->toBe('Proteina Test')
        ->and($detalle['comprobante'])->toBe('TICKET T001-000001');
});
