<?php

namespace App\Services;

use App\Models\Core\MovimientoInventario;
use App\Models\Core\Producto;
use Illuminate\Support\Facades\DB;

class InventarioService
{
    /**
     * Registrar movimiento de inventario
     */
    public function registrarMovimiento(
        int $productoId,
        string $tipo,
        int $cantidad,
        string $motivo,
        ?int $referenciaId = null,
        ?string $referenciaTipo = null,
        ?string $observaciones = null
    ): MovimientoInventario {
        return DB::transaction(function () use ($productoId, $tipo, $cantidad, $motivo, $referenciaId, $referenciaTipo, $observaciones) {
            $producto = Producto::findOrFail($productoId);

            // Actualizar stock según el tipo de movimiento
            if ($tipo === 'entrada') {
                $producto->stock_actual += $cantidad;
            } elseif ($tipo === 'salida') {
                if ($producto->stock_actual < $cantidad) {
                    throw new \Exception('Stock insuficiente para este movimiento.');
                }
                $producto->stock_actual -= $cantidad;
            } elseif ($tipo === 'ajuste') {
                $producto->stock_actual = $cantidad;
            }

            $producto->save();

            // Registrar movimiento
            return MovimientoInventario::create([
                'producto_id' => $productoId,
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'motivo' => $motivo,
                'referencia_id' => $referenciaId,
                'referencia_tipo' => $referenciaTipo,
                'usuario_id' => auth()->id(),
                'fecha' => now(),
                'observaciones' => $observaciones,
            ]);
        });
    }

    /**
     * Registrar salida por venta
     */
    public function registrarSalidaVenta(int $productoId, int $cantidad, int $ventaId): MovimientoInventario
    {
        return $this->registrarMovimiento(
            $productoId,
            'salida',
            $cantidad,
            'Venta',
            $ventaId,
            'App\Models\Core\Venta'
        );
    }

    /**
     * Ajustar inventario manualmente
     */
    public function ajustarInventario(int $productoId, int $nuevoStock, ?string $observaciones = null): MovimientoInventario
    {
        $producto = Producto::findOrFail($productoId);
        $diferencia = $nuevoStock - $producto->stock_actual;

        if ($diferencia > 0) {
            return $this->registrarMovimiento(
                $productoId,
                'entrada',
                $diferencia,
                'Ajuste de inventario',
                null,
                null,
                $observaciones
            );
        } elseif ($diferencia < 0) {
            return $this->registrarMovimiento(
                $productoId,
                'salida',
                abs($diferencia),
                'Ajuste de inventario',
                null,
                null,
                $observaciones
            );
        }

        // Si no hay diferencia, crear un ajuste con cantidad 0
        return $this->registrarMovimiento(
            $productoId,
            'ajuste',
            $nuevoStock,
            'Ajuste de inventario',
            null,
            null,
            $observaciones
        );
    }
}
