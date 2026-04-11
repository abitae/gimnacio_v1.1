<?php

namespace Database\Factories;

use App\Models\Core\RentableSpace;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentableSpace>
 */
class RentableSpaceFactory extends Factory
{
    protected $model = RentableSpace::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement(['Cancha', 'Salón', 'Sala', 'Auditorio']).' '.fake()->unique()->numberBetween(1, 999_999_999),
            'descripcion' => fake()->sentence(),
            'capacidad' => fake()->numberBetween(4, 40),
            'estado' => 'activo',
            'color_calendario' => fake()->randomElement(array_keys(RentableSpace::COLORES_CALENDARIO)),
        ];
    }
}
