<?php

namespace Database\Seeders;

use App\Models\Core\Caja;
use App\Models\User;
use Illuminate\Database\Seeder;

class CajaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->warn('No hay usuarios. Ejecuta DatabaseSeeder primero.');
            return;
        }

        // Crear una caja cerrada de ejemplo (del día anterior) solo si no existe
        $cajaCerrada = Caja::where('estado', 'cerrada')
            ->whereDate('fecha_apertura', now()->subDay())
            ->first();

        if (!$cajaCerrada) {
            Caja::create([
                'usuario_id' => $user->id,
                'saldo_inicial' => 500.00,
                'saldo_final' => 1250.00,
                'fecha_apertura' => now()->subDay()->setTime(8, 0, 0),
                'fecha_cierre' => now()->subDay()->setTime(20, 0, 0),
                'estado' => 'cerrada',
                'observaciones_apertura' => 'Caja inicial del día anterior',
                'observaciones_cierre' => 'Cierre normal del día',
            ]);
        }

        // Crear una caja abierta actual (para pruebas) solo si no existe
        $cajaAbierta = Caja::where('estado', 'abierta')->first();

        if (!$cajaAbierta) {
            Caja::create([
                'usuario_id' => $user->id,
                'saldo_inicial' => 500.00,
                'saldo_final' => null,
                'fecha_apertura' => now()->setTime(8, 0, 0),
                'fecha_cierre' => null,
                'estado' => 'abierta',
                'observaciones_apertura' => 'Caja abierta para pruebas',
                'observaciones_cierre' => null,
            ]);
        }

        $this->command->info('Cajas creadas exitosamente.');
    }
}
