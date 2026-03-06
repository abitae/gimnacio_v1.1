<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Models\User;
use Illuminate\Database\Seeder;

class EvaluacionMedidasNutricionSeeder extends Seeder
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

        // Crear nutricionista de ejemplo
        $nutricionista = User::firstOrCreate(
            ['email' => 'nutricionista@gimnasio.com'],
            [
                'name' => 'Jasmin Nutricionista',
                'password' => bcrypt('password'),
                'estado' => 'activo',
            ]
        );
        if (! $nutricionista->hasRole('nutricionista')) {
            $nutricionista->assignRole('nutricionista');
        }

        // Evaluación inicial para el primer cliente
        EvaluacionMedidasNutricion::create([
            'cliente_id' => $clientes[0]->id,
            'peso' => 92.3,
            'estatura' => 1.60,
            'imc' => 36.05,
            'porcentaje_grasa' => 37.4,
            'porcentaje_musculo' => 32.39,
            'masa_muscular' => 29.9,
            'masa_grasa' => 34.5,
            'masa_osea' => 3.2,
            'masa_residual' => 24.7,
            'circunferencias' => [
                'estatura' => 160,
                'cuello' => 38,
                'brazo_normal' => 32,
                'brazo_contraido' => 35,
                'torax' => 105,
                'cintura' => 95,
                'cintura_baja' => 98,
                'cadera' => 102,
                'muslo' => 62,
                'gluteos' => 108,
                'pantorrilla' => 38,
            ],
            'presion_arterial' => '130/85',
            'frecuencia_cardiaca' => 78,
            'objetivo' => 'DEPORTES Ó SALUD',
            'nutricionista_id' => $nutricionista->id,
            'fecha_proxima_evaluacion' => now()->addMonth(),
            'estado' => 'completada',
            'observaciones' => 'Cliente con sobrepeso. Recomendado programa de pérdida de peso y fortalecimiento muscular.',
            'evaluado_por' => $user->id,
            'created_at' => now()->subDays(30),
        ]);

        // Evaluación de seguimiento para el primer cliente
        EvaluacionMedidasNutricion::create([
            'cliente_id' => $clientes[0]->id,
            'peso' => 88.5,
            'estatura' => 1.60,
            'imc' => 34.57,
            'porcentaje_grasa' => 35.2,
            'porcentaje_musculo' => 33.5,
            'masa_muscular' => 29.6,
            'masa_grasa' => 31.2,
            'masa_osea' => 3.2,
            'masa_residual' => 24.5,
            'circunferencias' => [
                'estatura' => 160,
                'cuello' => 37,
                'brazo_normal' => 31.5,
                'brazo_contraido' => 34.5,
                'torax' => 103,
                'cintura' => 92,
                'cintura_baja' => 95,
                'cadera' => 100,
                'muslo' => 61,
                'gluteos' => 106,
                'pantorrilla' => 37.5,
            ],
            'presion_arterial' => '125/80',
            'frecuencia_cardiaca' => 75,
            'objetivo' => 'DEPORTES Ó SALUD',
            'nutricionista_id' => $nutricionista->id,
            'fecha_proxima_evaluacion' => now()->addMonth(),
            'estado' => 'completada',
            'observaciones' => 'Buen progreso. Reducción de peso y mejora en composición corporal. Continuar con el programa.',
            'evaluado_por' => $user->id,
            'created_at' => now()->subDays(7),
        ]);

        // Evaluación inicial para el segundo cliente
        if ($clientes->count() > 1) {
            EvaluacionMedidasNutricion::create([
                'cliente_id' => $clientes[1]->id,
                'peso' => 75.5,
                'estatura' => 1.75,
                'imc' => 24.65,
                'porcentaje_grasa' => 18.5,
                'porcentaje_musculo' => 45.2,
                'masa_muscular' => 34.1,
                'masa_grasa' => 14.0,
                'masa_osea' => 3.8,
                'masa_residual' => 23.6,
                'circunferencias' => [
                    'estatura' => 175,
                    'cuello' => 38,
                    'brazo_normal' => 33,
                    'brazo_contraido' => 36,
                    'torax' => 100,
                    'cintura' => 85,
                    'cintura_baja' => 88,
                    'cadera' => 95,
                    'muslo' => 58,
                    'gluteos' => 98,
                    'pantorrilla' => 38,
                ],
                'presion_arterial' => '120/80',
                'frecuencia_cardiaca' => 72,
                'objetivo' => 'DEPORTES Ó SALUD',
                'nutricionista_id' => $nutricionista->id,
                'fecha_proxima_evaluacion' => now()->addMonths(2),
                'estado' => 'completada',
                'observaciones' => 'Cliente en buen estado físico. Recomendado programa de fuerza y resistencia.',
                'evaluado_por' => $user->id,
                'created_at' => now()->subDays(15),
            ]);
        }
    }
}
