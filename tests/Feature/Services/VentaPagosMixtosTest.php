<?php

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\ClientDebt;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Producto;
use App\Models\Core\VentaPago;
use App\Models\System\Empresa;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use App\Services\VentaService;

function crearSucursalVentaPagosMixtos(string $codigo): Sucursal
{
    $empresa = Empresa::query()->create([
        'nombre' => 'Empresa '.$codigo,
        'estado' => 'activa',
    ]);

    return Sucursal::query()->create([
        'empresa_id' => $empresa->id,
        'codigo' => $codigo,
        'nombre' => 'Sucursal '.$codigo,
        'estado' => 'activa',
        'es_principal' => true,
    ]);
}

function prepararCajaVentaPagosMixtos(User $user, Sucursal $sucursal): void
{
    app(SucursalContext::class)->setDelegateContext($sucursal->id, $sucursal->empresa_id);

    Caja::query()->create([
        'usuario_id' => $user->id,
        'saldo_inicial' => 0,
        'fecha_apertura' => now(),
        'estado' => 'abierta',
        'sucursal_id' => $sucursal->id,
    ]);
}

afterEach(function () {
    app(SucursalContext::class)->clearDelegateContext();
});

it('registra pagos mixtos POS y caja por cada metodo', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $sucursal = crearSucursalVentaPagosMixtos('VPM-A');
    prepararCajaVentaPagosMixtos($user, $sucursal);

    $producto = Producto::factory()->create([
        'precio_venta' => 100,
        'stock_actual' => 10,
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);
    $efectivo = PaymentMethod::factory()->create(['nombre' => 'Efectivo', 'sucursal_id' => $sucursal->id]);
    $yape = PaymentMethod::factory()->create(['nombre' => 'Yape', 'sucursal_id' => $sucursal->id]);

    $venta = app(VentaService::class)->procesarVenta([
        'tipo_comprador' => 'cliente_solo_venta',
        'pagos' => [
            ['payment_method_id' => $efectivo->id, 'monto' => 50],
            ['payment_method_id' => $yape->id, 'monto' => 50, 'numero_operacion' => 'OP-1'],
        ],
        'items' => [
            ['tipo' => 'producto', 'id' => $producto->id, 'cantidad' => 1],
        ],
    ]);

    expect($venta->fresh('pagos')->pagos)->toHaveCount(2)
        ->and((float) $venta->pagos()->sum('monto'))->toBe(100.0)
        ->and($venta->fresh()->metodo_pago)->toBe('Mixto');

    $movimientos = CajaMovimiento::query()
        ->where('referencia_tipo', VentaPago::class)
        ->whereIn('referencia_id', $venta->pagos()->pluck('id'))
        ->get();

    expect($movimientos)->toHaveCount(2)
        ->and((float) $movimientos->sum('monto'))->toBe(100.0);
});

it('crea deuda de credito con saldo pendiente usando pagos iniciales mixtos', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $sucursal = crearSucursalVentaPagosMixtos('VPM-B');
    prepararCajaVentaPagosMixtos($user, $sucursal);

    $cliente = \App\Models\Core\Cliente::factory()->create(['sucursal_id' => $sucursal->id]);
    $producto = Producto::factory()->create([
        'precio_venta' => 120,
        'stock_actual' => 10,
        'estado' => 'activo',
        'sucursal_id' => $sucursal->id,
    ]);
    $efectivo = PaymentMethod::factory()->create(['nombre' => 'Efectivo', 'sucursal_id' => $sucursal->id]);
    $yape = PaymentMethod::factory()->create(['nombre' => 'Yape', 'sucursal_id' => $sucursal->id]);

    $venta = app(VentaService::class)->procesarVenta([
        'tipo_comprador' => 'cliente',
        'cliente_id' => $cliente->id,
        'es_credito' => true,
        'fecha_vencimiento_deuda' => now()->addDays(7)->toDateString(),
        'pagos' => [
            ['payment_method_id' => $efectivo->id, 'monto' => 20],
            ['payment_method_id' => $yape->id, 'monto' => 30],
        ],
        'items' => [
            ['tipo' => 'producto', 'id' => $producto->id, 'cantidad' => 1],
        ],
    ]);

    $debt = ClientDebt::query()->where('venta_id', $venta->id)->first();

    expect($debt)->not->toBeNull()
        ->and((float) $debt->monto_pagado)->toBe(50.0)
        ->and((float) $debt->saldo_pendiente)->toBe(70.0)
        ->and($debt->estado)->toBe('parcial')
        ->and((float) $venta->pagos()->sum('monto'))->toBe(50.0);
});
