<?php

namespace Database\Factories;

use App\Models\Core\CategoriaServicio;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaServicio>
 */
class CategoriaServicioFactory extends Factory
{
    protected $model = CategoriaServicio::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->words(2, true),
            'descripcion' => fake()->sentence(),
            'estado' => 'activa',
        ];
    }
}
