<?php

namespace Database\Factories;

use App\Models\Crm\CrmStage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmStage>
 */
class CrmStageFactory extends Factory
{
    protected $model = CrmStage::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'orden' => fake()->unique()->numberBetween(1, 20),
            'is_default' => false,
            'is_won' => false,
            'is_lost' => false,
        ];
    }
}
