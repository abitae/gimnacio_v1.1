<?php

namespace App\Services\Pos;

use App\Models\Core\Venta;
use App\Services\PosAlquilerReservaService;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;

class PosSaleOrchestrator
{
    public function __construct(
        protected VentaService $ventaService,
        protected PosAlquilerReservaService $posAlquilerReservaService,
    ) {}

    /**
     * Punto único de confirmación de venta desde POS.
     *
     * @param  array<string, mixed>  $saleData
     * @param  array<int, array<string, mixed>>  $carritoSnapshot
     * @param  array{fecha: string, hora_inicio: string, hora_fin: string}  $rentalSlot
     */
    public function completeSale(
        array $saleData,
        array $carritoSnapshot,
        bool $tieneAlquiler,
        array $rentalSlot = []
    ): Venta {
        return DB::transaction(function () use ($saleData, $carritoSnapshot, $tieneAlquiler, $rentalSlot) {
            $venta = $this->ventaService->procesarVenta($saleData);

            if ($tieneAlquiler) {
                $this->posAlquilerReservaService->crearDesdeVenta($venta, $carritoSnapshot, $rentalSlot);
            }

            return $venta;
        });
    }
}
