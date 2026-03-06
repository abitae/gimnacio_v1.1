<?php

namespace Database\Seeders;

use App\Models\Core\Caja;
use App\Models\Core\CajaMovimiento;
use App\Models\Core\Venta;
use App\Models\User;
use Illuminate\Database\Seeder;

class CajaMovimientoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $cajaAbierta = Caja::where('estado', 'abierta')->first();
        $cajaCerrada = Caja::where('estado', 'cerrada')->first();

        if (!$user) {
            $this->command->warn('No hay usuarios. Ejecuta DatabaseSeeder primero.');
            return;
        }

        // Solo crear movimientos si hay ventas existentes
        $ventas = Venta::all();

        if ($ventas->isEmpty()) {
            $this->command->warn('No hay ventas. Los movimientos de caja se crearán automáticamente al procesar ventas.');
            return;
        }

        // Crear movimientos de entrada para ventas en caja cerrada
        if ($cajaCerrada) {
            foreach ($ventas->take(3) as $venta) {
                // Verificar si ya existe un movimiento para esta venta
                $movimientoExistente = CajaMovimiento::where('referencia_tipo', 'App\Models\Core\Venta')
                    ->where('referencia_id', $venta->id)
                    ->first();

                if (!$movimientoExistente) {
                    CajaMovimiento::create([
                        'caja_id' => $cajaCerrada->id,
                        'tipo' => 'entrada',
                        'monto' => $venta->total,
                        'concepto' => "Venta POS - {$venta->numero_venta}",
                        'referencia_tipo' => 'App\Models\Core\Venta',
                        'referencia_id' => $venta->id,
                        'usuario_id' => $user->id,
                        'observaciones' => "Método de pago: {$venta->metodo_pago}, Comprobante: " . strtoupper($venta->tipo_comprobante) . " {$venta->serie_comprobante}-{$venta->numero_comprobante}",
                        'fecha_movimiento' => $venta->fecha_venta,
                    ]);
                }
            }

            // Crear algunos movimientos de salida (gastos) en caja cerrada
            CajaMovimiento::create([
                'caja_id' => $cajaCerrada->id,
                'tipo' => 'salida',
                'monto' => 50.00,
                'concepto' => 'Gasto - Compra de suministros',
                'referencia_tipo' => null,
                'referencia_id' => null,
                'usuario_id' => $user->id,
                'observaciones' => 'Compra de material de limpieza',
                'fecha_movimiento' => now()->subDay()->setTime(14, 0, 0),
            ]);

            CajaMovimiento::create([
                'caja_id' => $cajaCerrada->id,
                'tipo' => 'salida',
                'monto' => 30.00,
                'concepto' => 'Retiro de efectivo',
                'referencia_tipo' => null,
                'referencia_id' => null,
                'usuario_id' => $user->id,
                'observaciones' => 'Retiro para cambio',
                'fecha_movimiento' => now()->subDay()->setTime(16, 0, 0),
            ]);
        }

        // Crear movimientos de entrada para ventas en caja abierta
        if ($cajaAbierta) {
            foreach ($ventas->skip(3)->take(2) as $venta) {
                // Verificar si ya existe un movimiento para esta venta
                $movimientoExistente = CajaMovimiento::where('referencia_tipo', 'App\Models\Core\Venta')
                    ->where('referencia_id', $venta->id)
                    ->first();

                if (!$movimientoExistente) {
                    CajaMovimiento::create([
                        'caja_id' => $cajaAbierta->id,
                        'tipo' => 'entrada',
                        'monto' => $venta->total,
                        'concepto' => "Venta POS - {$venta->numero_venta}",
                        'referencia_tipo' => 'App\Models\Core\Venta',
                        'referencia_id' => $venta->id,
                        'usuario_id' => $user->id,
                        'observaciones' => "Método de pago: {$venta->metodo_pago}, Comprobante: " . strtoupper($venta->tipo_comprobante) . " {$venta->serie_comprobante}-{$venta->numero_comprobante}",
                        'fecha_movimiento' => $venta->fecha_venta,
                    ]);
                }
            }
        }

        $this->command->info('Movimientos de caja creados exitosamente.');
    }
}
