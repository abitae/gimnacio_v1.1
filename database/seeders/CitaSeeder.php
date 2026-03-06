<?php

namespace Database\Seeders;

use App\Models\Core\Cita;
use App\Models\Core\Cliente;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Models\User;
use Illuminate\Database\Seeder;

class CitaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();
        $clientes = Cliente::all();

        if ($clientes->isEmpty()) {
            $this->command->warn('No hay clientes. Ejecuta ClienteSeeder primero.');
            return;
        }

        // Obtener nutricionista
        $nutricionista = User::where('email', 'nutricionista@gimnasio.com')->first();
        if (!$nutricionista) {
            $this->command->warn('No hay nutricionista. Ejecuta EvaluacionMedidasNutricionSeeder primero.');
            return;
        }

        // Obtener usuarios con rol trainer
        $trainers = User::role('trainer')->get();

        // Obtener evaluaciones
        $evaluaciones = EvaluacionMedidasNutricion::all();

        // Cita de evaluación programada
        Cita::create([
            'cliente_id' => $clientes[0]->id,
            'tipo' => 'evaluacion',
            'fecha_hora' => now()->addDays(7)->setTime(10, 0),
            'duracion_minutos' => 60,
            'nutricionista_id' => $nutricionista->id,
            'estado' => 'programada',
            'observaciones' => 'Evaluación de seguimiento mensual',
            'created_by' => $user->id,
        ]);

        // Cita de consulta nutricional completada (con evaluación asociada)
        if ($evaluaciones->isNotEmpty()) {
            Cita::create([
                'cliente_id' => $clientes[0]->id,
                'tipo' => 'consulta_nutricional',
                'fecha_hora' => now()->subDays(7)->setTime(14, 0),
                'duracion_minutos' => 45,
                'nutricionista_id' => $nutricionista->id,
                'estado' => 'completada',
                'observaciones' => 'Consulta de seguimiento nutricional',
                'evaluacion_medidas_nutricion_id' => $evaluaciones->first()->id,
                'created_by' => $user->id,
            ]);
        }

        // Cita con trainer (usuario con rol trainer)
        if ($trainers->isNotEmpty() && $clientes->count() > 1) {
            Cita::create([
                'cliente_id' => $clientes[1]->id,
                'tipo' => 'seguimiento',
                'fecha_hora' => now()->addDays(3)->setTime(16, 0),
                'duracion_minutos' => 60,
                'trainer_user_id' => $trainers->first()->id,
                'estado' => 'confirmada',
                'observaciones' => 'Seguimiento de programa de entrenamiento',
                'created_by' => $user->id,
            ]);
        }

        // Cita cancelada
        Cita::create([
            'cliente_id' => $clientes[0]->id,
            'tipo' => 'evaluacion',
            'fecha_hora' => now()->subDays(2)->setTime(11, 0),
            'duracion_minutos' => 60,
            'nutricionista_id' => $nutricionista->id,
            'estado' => 'cancelada',
            'observaciones' => 'Cliente canceló por motivos personales',
            'created_by' => $user->id,
        ]);
    }
}
