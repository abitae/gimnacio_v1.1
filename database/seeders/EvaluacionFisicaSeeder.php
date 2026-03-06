<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Core\EvaluacionFisica;
use App\Models\User;
use Illuminate\Database\Seeder;

class EvaluacionFisicaSeeder extends Seeder
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

        // Evaluación inicial para el primer cliente
        EvaluacionFisica::create([
            'cliente_id' => $clientes[0]->id,
            'peso' => 75.5,
            'estatura' => 1.75,
            'imc' => 24.65,
            'porcentaje_grasa' => 18.5,
            'porcentaje_musculo' => 45.2,
            'perimetros_corporales' => [
                'pecho' => 100,
                'cintura' => 85,
                'cadera' => 95,
                'brazo_derecho' => 32,
                'brazo_izquierdo' => 31.5,
                'muslo_derecho' => 58,
                'muslo_izquierdo' => 57.5,
            ],
            'presion_arterial' => '120/80',
            'frecuencia_cardiaca' => 72,
            'observaciones' => 'Cliente en buen estado físico. Recomendado programa de fuerza y resistencia.',
            'evaluado_por' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        // Evaluación de seguimiento para el primer cliente
        EvaluacionFisica::create([
            'cliente_id' => $clientes[0]->id,
            'peso' => 73.2,
            'estatura' => 1.75,
            'imc' => 23.91,
            'porcentaje_grasa' => 16.8,
            'porcentaje_musculo' => 47.5,
            'perimetros_corporales' => [
                'pecho' => 102,
                'cintura' => 82,
                'cadera' => 93,
                'brazo_derecho' => 33,
                'brazo_izquierdo' => 32.5,
                'muslo_derecho' => 59,
                'muslo_izquierdo' => 58.5,
            ],
            'presion_arterial' => '118/78',
            'frecuencia_cardiaca' => 68,
            'observaciones' => 'Excelente progreso. Reducción de grasa y aumento de masa muscular. Continuar con el programa actual.',
            'evaluado_por' => $user->id,
            'created_at' => now()->subDays(7),
        ]);

        // Evaluación inicial para el segundo cliente
        if ($clientes->count() > 1) {
            EvaluacionFisica::create([
                'cliente_id' => $clientes[1]->id,
                'peso' => 65.0,
                'estatura' => 1.65,
                'imc' => 23.88,
                'porcentaje_grasa' => 22.0,
                'porcentaje_musculo' => 38.5,
                'perimetros_corporales' => [
                    'pecho' => 88,
                    'cintura' => 72,
                    'cadera' => 92,
                    'brazo_derecho' => 28,
                    'brazo_izquierdo' => 27.5,
                    'muslo_derecho' => 52,
                    'muslo_izquierdo' => 51.5,
                ],
                'presion_arterial' => '115/75',
                'frecuencia_cardiaca' => 70,
                'observaciones' => 'Cliente con buena condición física base. Recomendado programa de tonificación y cardio.',
                'evaluado_por' => $user->id,
                'created_at' => now()->subDays(15),
            ]);
        }
    }
}
