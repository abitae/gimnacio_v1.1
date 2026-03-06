<?php

namespace Database\Seeders;

use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\User;
use Illuminate\Database\Seeder;

class AsistenciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $clienteMembresias = ClienteMembresia::where('estado', 'activa')->get();

        if ($clienteMembresias->isEmpty()) {
            $this->command->warn('No hay membresías activas. Ejecuta ClienteMembresiaSeeder primero.');
            return;
        }

        // Asistencias para la primera membresía activa
        $membresia1 = $clienteMembresias[0];
        $cliente1 = Cliente::find($membresia1->cliente_id);

        // Asistencia manual de hoy
        Asistencia::create([
            'cliente_id' => $cliente1->id,
            'cliente_membresia_id' => $membresia1->id,
            'fecha_hora_ingreso' => now()->setTime(8, 30),
            'fecha_hora_salida' => now()->setTime(10, 15),
            'origen' => 'manual',
            'valido_por_membresia' => true,
            'registrada_por' => $user->id,
        ]);

        // Asistencia de ayer
        Asistencia::create([
            'cliente_id' => $cliente1->id,
            'cliente_membresia_id' => $membresia1->id,
            'fecha_hora_ingreso' => now()->subDay()->setTime(18, 0),
            'fecha_hora_salida' => now()->subDay()->setTime(19, 30),
            'origen' => 'app',
            'valido_por_membresia' => true,
            'registrada_por' => null,
        ]);

        // Asistencia desde BioTime
        Asistencia::create([
            'cliente_id' => $cliente1->id,
            'cliente_membresia_id' => $membresia1->id,
            'fecha_hora_ingreso' => now()->subDays(2)->setTime(7, 0),
            'fecha_hora_salida' => now()->subDays(2)->setTime(8, 45),
            'origen' => 'biotime',
            'valido_por_membresia' => true,
            'registrada_por' => null,
        ]);

        // Asistencias para la segunda membresía activa
        if ($clienteMembresias->count() > 1) {
            $membresia2 = $clienteMembresias[1];
            $cliente2 = Cliente::find($membresia2->cliente_id);

            Asistencia::create([
                'cliente_id' => $cliente2->id,
                'cliente_membresia_id' => $membresia2->id,
                'fecha_hora_ingreso' => now()->setTime(9, 0),
                'fecha_hora_salida' => null, // Aún en el gimnasio
                'origen' => 'manual',
                'valido_por_membresia' => true,
                'registrada_por' => $user->id,
            ]);

            Asistencia::create([
                'cliente_id' => $cliente2->id,
                'cliente_membresia_id' => $membresia2->id,
                'fecha_hora_ingreso' => now()->subDays(3)->setTime(17, 30),
                'fecha_hora_salida' => now()->subDays(3)->setTime(19, 0),
                'origen' => 'app',
                'valido_por_membresia' => true,
                'registrada_por' => null,
            ]);
        }
    }
}
