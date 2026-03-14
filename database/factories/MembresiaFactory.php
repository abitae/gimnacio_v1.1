<?php

namespace Database\Factories;

use App\Models\Core\Membresia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membresia>
 */
class MembresiaFactory extends Factory
{
    protected $model = Membresia::class;

    public function definition(): array
    {
        $permiteCuotas = fake()->boolean(40);

        return [
            'nombre' => 'Plan ' . fake()->unique()->words(2, true),
            'descripcion' => fake()->sentence(),
            'duracion_dias' => fake()->randomElement([30, 60, 90, 180, 365]),
            'precio_base' => fake()->randomFloat(2, 80, 1200),
            'permite_cuotas' => $permiteCuotas,
            'numero_cuotas_default' => $permiteCuotas ? fake()->numberBetween(2, 6) : null,
            'frecuencia_cuotas_default' => $permiteCuotas ? fake()->randomElement(['mensual', 'quincenal']) : null,
            'cuota_inicial_monto' => $permiteCuotas ? fake()->randomFloat(2, 20, 150) : null,
            'cuota_inicial_porcentaje' => null,
            'tipo_acceso' => fake()->randomElement(['ilimitado', 'por_visitas']),
            'max_visitas_dia' => fake()->boolean(20) ? fake()->numberBetween(1, 3) : null,
            'permite_congelacion' => fake()->boolean(50),
            'max_dias_congelacion' => fake()->boolean(50) ? fake()->numberBetween(3, 30) : null,
            'estado' => 'activa',
        ];
    }

    public function conCuotas(): static
    {
        return $this->state(fn () => [
            'permite_cuotas' => true,
            'numero_cuotas_default' => 3,
            'frecuencia_cuotas_default' => 'mensual',
            'cuota_inicial_monto' => 50,
        ]);
    }
}
