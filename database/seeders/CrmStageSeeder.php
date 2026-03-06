<?php

namespace Database\Seeders;

use App\Models\Crm\CrmStage;
use Illuminate\Database\Seeder;

class CrmStageSeeder extends Seeder
{
    public function run(): void
    {
        $stages = [
            ['nombre' => 'Nuevo', 'orden' => 1, 'is_default' => true, 'is_won' => false, 'is_lost' => false],
            ['nombre' => 'Contactado', 'orden' => 2, 'is_default' => false, 'is_won' => false, 'is_lost' => false],
            ['nombre' => 'Interesado', 'orden' => 3, 'is_default' => false, 'is_won' => false, 'is_lost' => false],
            ['nombre' => 'Agendó visita', 'orden' => 4, 'is_default' => false, 'is_won' => false, 'is_lost' => false],
            ['nombre' => 'Visitó/Prueba', 'orden' => 5, 'is_default' => false, 'is_won' => false, 'is_lost' => false],
            ['nombre' => 'Negociación', 'orden' => 6, 'is_default' => false, 'is_won' => false, 'is_lost' => false],
            ['nombre' => 'Cerrado-Ganado', 'orden' => 7, 'is_default' => false, 'is_won' => true, 'is_lost' => false],
            ['nombre' => 'Cerrado-Perdido', 'orden' => 8, 'is_default' => false, 'is_won' => false, 'is_lost' => true],
            ['nombre' => 'No responde', 'orden' => 9, 'is_default' => false, 'is_won' => false, 'is_lost' => true],
        ];

        foreach ($stages as $stage) {
            CrmStage::firstOrCreate(
                ['nombre' => $stage['nombre']],
                $stage
            );
        }
    }
}
