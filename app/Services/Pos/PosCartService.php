<?php

namespace App\Services\Pos;

use App\Data\Pos\CartTotals;
use App\Models\Core\DiscountCoupon;
use InvalidArgumentException;

class PosCartService
{
    public const IGV_RATE_NUMERATOR = 18;

    public const IGV_RATE_DENOMINATOR = 118;

    public function calculateSubtotal(array $carrito): float
    {
        $subtotal = 0.0;

        foreach ($carrito as $item) {
            $precioItem = ((float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 1))
                - (float) ($item['descuento'] ?? 0);
            $subtotal += $precioItem;
        }

        return round($subtotal, 2);
    }

    public function calculateTotals(
        array $carrito,
        float $descuentoManual = 0,
        float $montoDescuentoCupon = 0
    ): CartTotals {
        $subtotal = $this->calculateSubtotal($carrito);
        $base = max(0, $subtotal - $descuentoManual - $montoDescuentoCupon);
        $igv = round($base * self::IGV_RATE_NUMERATOR / self::IGV_RATE_DENOMINATOR, 2);
        $subtotalSinIgv = round($base - $igv, 2);

        return new CartTotals(
            subtotal: $subtotal,
            descuentoManual: round($descuentoManual, 2),
            montoDescuentoCupon: round($montoDescuentoCupon, 2),
            base: round($base, 2),
            igv: $igv,
            subtotalSinIgv: $subtotalSinIgv,
            total: round($base, 2),
        );
    }

    public function carritoTieneAlquiler(array $carrito): bool
    {
        return collect($carrito)->contains(
            fn (array $item) => ($item['tipo'] ?? '') === 'alquiler'
        );
    }

    /**
     * @return array{coupon_id: int, monto: float}
     *
     * @throws InvalidArgumentException
     */
    public function resolveCouponDiscount(string $codigo, array $carrito, float $descuentoManual): array
    {
        $codigo = strtoupper(trim($codigo));
        if ($codigo === '') {
            throw new InvalidArgumentException('Ingresa el código del cupón.');
        }

        $coupon = DiscountCoupon::query()->where('codigo', $codigo)->first();
        if (! $coupon) {
            throw new InvalidArgumentException('Cupón no encontrado.');
        }
        if (! $coupon->puedeUsarse()) {
            throw new InvalidArgumentException('El cupón no está vigente o ya alcanzó el límite de usos.');
        }
        if (! $coupon->aplicaA('pos')) {
            throw new InvalidArgumentException('Este cupón no aplica para ventas en POS.');
        }

        $base = $this->calculateSubtotal($carrito) - $descuentoManual;
        $monto = $coupon->calcularDescuento($base);

        return [
            'coupon_id' => (int) $coupon->id,
            'monto' => round((float) $monto, 2),
        ];
    }
}
