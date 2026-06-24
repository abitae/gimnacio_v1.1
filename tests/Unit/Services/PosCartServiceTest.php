<?php

use App\Services\Pos\PosCartService;

it('calculates cart subtotal with line discounts', function () {
    $service = app(PosCartService::class);

    $subtotal = $service->calculateSubtotal([
        ['precio' => 100, 'cantidad' => 2, 'descuento' => 10],
        ['precio' => 50, 'cantidad' => 1, 'descuento' => 0],
    ]);

    expect($subtotal)->toBe(240.0);
});

it('calculates totals with manual discount and coupon', function () {
    $service = app(PosCartService::class);

    $totals = $service->calculateTotals(
        [['precio' => 118, 'cantidad' => 1, 'descuento' => 0]],
        descuentoManual: 18,
        montoDescuentoCupon: 0
    );

    expect($totals->total)->toBe(100.0);
    expect($totals->igv)->toBe(round(100 * 18 / 118, 2));
});

it('detects rental items in cart', function () {
    $service = app(PosCartService::class);

    expect($service->carritoTieneAlquiler([
        ['tipo' => 'producto', 'id' => 1],
    ]))->toBeFalse();

    expect($service->carritoTieneAlquiler([
        ['tipo' => 'alquiler', 'id' => 2],
    ]))->toBeTrue();
});
