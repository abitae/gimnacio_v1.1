<?php

namespace Database\Seeders;

use App\Models\Core\Cliente;
use App\Models\Core\NutritionGoal;
use App\Models\User;
use Illuminate\Database\Seeder;

class NutritionGoalSeeder extends Seeder
{
    public function run(): void
    {
        $clientes = Cliente::limit(3)->get();
        if ($clientes->isEmpty()) {
            $this->command->warn('No hay clientes. NutritionGoalSeeder se omite.');
            return;
        }

        $trainer = User::first();
        if (! $trainer) {
            $this->command->warn('No hay usuarios. NutritionGoalSeeder se omite.');
            return;
        }

        $objetivos = array_keys(NutritionGoal::OBJETIVOS);

        foreach ($clientes as $cliente) {
            if (NutritionGoal::where('cliente_id', $cliente->id)->exists()) {
                continue;
            }
            NutritionGoal::create([
                'cliente_id' => $cliente->id,
                'trainer_user_id' => $trainer->id,
                'objetivo' => $objetivos[array_rand($objetivos)],
                'objetivo_personalizado' => null,
                'fecha_inicio' => now()->subDays(rand(10, 60)),
                'fecha_objetivo' => now()->addDays(rand(60, 120)),
                'observaciones' => 'Objetivo de ejemplo para seguimiento nutricional.',
                'estado' => 'activo',
            ]);
        }
    }
}
