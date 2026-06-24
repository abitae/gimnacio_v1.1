<?php

namespace App\Data\Pos;

final readonly class CartTotals
{
    public function __construct(
        public float $subtotal,
        public float $descuentoManual,
        public float $montoDescuentoCupon,
        public float $base,
        public float $igv,
        public float $subtotalSinIgv,
        public float $total,
    ) {}
}
