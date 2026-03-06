<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Membresia;
use App\Models\User;
use Illuminate\Database\Seeder;

class ClienteMembresiaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $clientes = Cliente::all();
        $membresias = Membresia::all();

        if ($clientes->isEmpty() || $membresias->isEmpty()) {
            $this->command->warn('No hay clientes o membresías. Ejecuta ClienteSeeder y MembresiaSeeder primero.');
            return;
        }

        // Membresía activa para el primer cliente
        ClienteMembresia::create([
            'cliente_id' => $clientes[0]->id,
            'membresia_id' => $membresias[0]->id, // Mensual Básica
            'fecha_inicio' => now()->subDays(10),
            'fecha_fin' => now()->addDays(20),
            'estado' => 'activa',
            'precio_lista' => 150.00,
            'descuento_monto' => 0.00,
            'precio_final' => 150.00,
            'asesor_id' => $user?->id,
            'canal_venta' => 'presencial',
            'fechas_congelacion' => null,
            'motivo_cancelacion' => null,
        ]);

        // Membresía activa para el segundo cliente
        ClienteMembresia::create([
            'cliente_id' => $clientes[1]->id,
            'membresia_id' => $membresias[1]->id, // Mensual Premium
            'fecha_inicio' => now()->subDays(5),
            'fecha_fin' => now()->addDays(25),
            'estado' => 'activa',
            'precio_lista' => 200.00,
            'descuento_monto' => 20.00,
            'precio_final' => 180.00,
            'asesor_id' => $user?->id,
            'canal_venta' => 'online',
            'fechas_congelacion' => null,
            'motivo_cancelacion' => null,
        ]);

        // Membresía vencida para el tercer cliente
        ClienteMembresia::create([
            'cliente_id' => $clientes[2]->id,
            'membresia_id' => $membresias[0]->id, // Mensual Básica
            'fecha_inicio' => now()->subDays(40),
            'fecha_fin' => now()->subDays(10),
            'estado' => 'vencida',
            'precio_lista' => 150.00,
            'descuento_monto' => 0.00,
            'precio_final' => 150.00,
            'asesor_id' => $user?->id,
            'canal_venta' => 'presencial',
            'fechas_congelacion' => null,
            'motivo_cancelacion' => null,
        ]);

        // Membresía congelada para el primer cliente
        ClienteMembresia::create([
            'cliente_id' => $clientes[0]->id,
            'membresia_id' => $membresias[2]->id, // Trimestral
            'fecha_inicio' => now()->subDays(60),
            'fecha_fin' => now()->addDays(30),
            'estado' => 'congelada',
            'precio_lista' => 400.00,
            'descuento_monto' => 50.00,
            'precio_final' => 350.00,
            'asesor_id' => $user?->id,
            'canal_venta' => 'presencial',
            'fechas_congelacion' => [
                [
                    'fecha_inicio' => now()->subDays(20)->toDateString(),
                    'fecha_fin' => now()->subDays(10)->toDateString(),
                    'motivo' => 'Viaje de trabajo',
                ],
            ],
            'motivo_cancelacion' => null,
        ]);
    }
}
