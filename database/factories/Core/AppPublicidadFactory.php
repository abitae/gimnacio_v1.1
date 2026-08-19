<?php

namespace Database\Factories\Core;

use App\Models\Core\AppPublicidad;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AppPublicidad>
 */
class AppPublicidadFactory extends Factory
{
    protected $model = AppPublicidad::class;

    public function definition(): array
    {
        return [
            'titulo' => fake()->words(3, true),
            'imagen' => 'app-publicidad/demo.jpg',
            'enlace_url' => null,
            'orden' => 0,
            'estado' => 'activo',
        ];
    }
}
