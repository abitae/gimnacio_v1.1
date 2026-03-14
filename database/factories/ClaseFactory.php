<?php

namespace Database\Factories;

use App\Models\Core\Clase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clase>
 */
class ClaseFactory extends Factory
{
    protected $model = Clase::class;

    public function definition(): array
    {
        $tipo = fake()->randomElement(['sesion', 'paquete']);

        return [
            'codigo' => 'CLA-' . fake()->unique()->numerify('###'),
            'nombre' => fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'tipo' => $tipo,
            'precio_sesion' => $tipo === 'sesion' ? fake()->randomFloat(2, 15, 60) : null,
            'precio_paquete' => $tipo === 'paquete' ? fake()->randomFloat(2, 80, 400) : null,
            'sesiones_paquete' => $tipo === 'paquete' ? fake()->numberBetween(4, 16) : null,
            'instructor_id' => User::factory(),
            'estado' => 'activo',
        ];
    }
}
