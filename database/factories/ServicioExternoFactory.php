<?php

namespace Database\Factories;

use App\Models\Core\CategoriaServicio;
use App\Models\Core\ServicioExterno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServicioExterno>
 */
class ServicioExternoFactory extends Factory
{
    protected $model = ServicioExterno::class;

    public function definition(): array
    {
        return [
            'codigo' => 'SERV-' . fake()->unique()->numerify('####'),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'categoria_id' => CategoriaServicio::factory(),
            'precio' => fake()->randomFloat(2, 20, 300),
            'duracion_minutos' => fake()->randomElement([30, 45, 60, 75, 90]),
            'estado' => 'activo',
        ];
    }
}
