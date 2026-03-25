<?php

namespace Database\Seeders;

use App\Models\Core\Caja;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Pago;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Pagos de demostración ligados a cliente_membresias (modelo legacy).
 * Las matrículas modernas (cliente_matriculas) generan pagos vía ClienteMatriculaService o ClienteMatriculaDemoSeeder.
 */
class PagoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $clienteMembresias = ClienteMembresia::all();

        if ($clienteMembresias->isEmpty()) {
            $this->command->warn('No hay cliente_membresias. Ejecuta ClienteMembresiaSeeder o usa ClienteMatriculaDemoSeeder para el flujo con cliente_matriculas.');

            return;
        }

        // Obtener o crear caja para asociar pagos
        $cajaCerrada = Caja::where('estado', 'cerrada')->first();
        $cajaAbierta = Caja::where('estado', 'abierta')->first();

        // Pago completo para la primera membresía (en caja cerrada)
        Pago::create([
            'cliente_id' => $clienteMembresias[0]->cliente_id,
            'cliente_membresia_id' => $clienteMembresias[0]->id,
            'monto' => 150.00,
            'moneda' => 'PEN',
            'metodo_pago' => 'efectivo',
            'fecha_pago' => $clienteMembresias[0]->fecha_inicio,
            'es_pago_parcial' => false,
            'saldo_pendiente' => 0.00,
            'comprobante_tipo' => 'boleta',
            'comprobante_numero' => 'B001-000001',
            'registrado_por' => $user->id,
            'caja_id' => $cajaCerrada?->id,
        ]);

        // Pago completo para la segunda membresía (en caja cerrada)
        Pago::create([
            'cliente_id' => $clienteMembresias[1]->cliente_id,
            'cliente_membresia_id' => $clienteMembresias[1]->id,
            'monto' => 180.00,
            'moneda' => 'PEN',
            'metodo_pago' => 'tarjeta',
            'fecha_pago' => $clienteMembresias[1]->fecha_inicio,
            'es_pago_parcial' => false,
            'saldo_pendiente' => 0.00,
            'comprobante_tipo' => 'factura',
            'comprobante_numero' => 'F001-000001',
            'registrado_por' => $user->id,
            'caja_id' => $cajaCerrada?->id,
        ]);

        // Pago parcial para la tercera membresía (en caja cerrada)
        Pago::create([
            'cliente_id' => $clienteMembresias[2]->cliente_id,
            'cliente_membresia_id' => $clienteMembresias[2]->id,
            'monto' => 75.00,
            'moneda' => 'PEN',
            'metodo_pago' => 'transferencia',
            'fecha_pago' => $clienteMembresias[2]->fecha_inicio,
            'es_pago_parcial' => true,
            'saldo_pendiente' => 75.00,
            'comprobante_tipo' => 'boleta',
            'comprobante_numero' => 'B001-000002',
            'registrado_por' => $user->id,
            'caja_id' => $cajaCerrada?->id,
        ]);

        // Segundo pago para completar la tercera membresía (en caja abierta si existe)
        Pago::create([
            'cliente_id' => $clienteMembresias[2]->cliente_id,
            'cliente_membresia_id' => $clienteMembresias[2]->id,
            'monto' => 75.00,
            'moneda' => 'PEN',
            'metodo_pago' => 'efectivo',
            'fecha_pago' => $clienteMembresias[2]->fecha_inicio->copy()->addDays(5),
            'es_pago_parcial' => false,
            'saldo_pendiente' => 0.00,
            'comprobante_tipo' => 'boleta',
            'comprobante_numero' => 'B001-000003',
            'registrado_por' => $user->id,
            'caja_id' => $cajaAbierta?->id,
        ]);
    }
}
