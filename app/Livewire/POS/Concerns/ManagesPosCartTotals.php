<?php

namespace App\Livewire\POS\Concerns;

use App\Services\Pos\PosCartService;

/**
 * Totales del carrito POS — delegados a PosCartService (sin fórmulas en Livewire).
 */
trait ManagesPosCartTotals
{
    protected PosCartService $posCartService;

    public function getCarritoTieneAlquilerProperty(): bool
    {
        return $this->posCartService->carritoTieneAlquiler($this->carrito);
    }

    public function getSubtotalProperty(): float
    {
        return $this->posCartService->calculateTotals(
            $this->carrito,
            (float) $this->descuento,
            (float) $this->montoDescuentoCupon
        )->subtotal;
    }

    public function getBaseParaTotalProperty(): float
    {
        return $this->posCartService->calculateTotals(
            $this->carrito,
            (float) $this->descuento,
            (float) $this->montoDescuentoCupon
        )->base;
    }

    public function getIgvProperty(): float
    {
        return $this->posCartService->calculateTotals(
            $this->carrito,
            (float) $this->descuento,
            (float) $this->montoDescuentoCupon
        )->igv;
    }

    public function getSubtotalSinIgvProperty(): float
    {
        return $this->posCartService->calculateTotals(
            $this->carrito,
            (float) $this->descuento,
            (float) $this->montoDescuentoCupon
        )->subtotalSinIgv;
    }

    public function getTotalProperty(): float
    {
        return $this->posCartService->calculateTotals(
            $this->carrito,
            (float) $this->descuento,
            (float) $this->montoDescuentoCupon
        )->total;
    }
}
