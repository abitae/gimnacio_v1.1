<?php

namespace Database\Factories;

use App\Models\Core\DiscountCoupon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountCoupon>
 */
class DiscountCouponFactory extends Factory
{
    protected $model = DiscountCoupon::class;

    public function definition(): array
    {
        return [
            'codigo' => strtoupper(fake()->unique()->bothify('PROMO##??')),
            'nombre' => 'Cupón ' . fake()->words(2, true),
            'descripcion' => fake()->sentence(),
            'tipo_descuento' => 'monto_fijo',
            'valor_descuento' => fake()->randomFloat(2, 5, 80),
            'fecha_inicio' => now()->subDays(fake()->numberBetween(0, 10))->toDateString(),
            'fecha_vencimiento' => now()->addDays(fake()->numberBetween(7, 90))->toDateString(),
            'cantidad_max_usos' => fake()->optional()->numberBetween(10, 500),
            'cantidad_usada' => 0,
            'aplica_a' => fake()->randomElement(['todos', 'pos', 'membresias', 'matriculas']),
            'estado' => 'activo',
        ];
    }

    public function expirado(): static
    {
        return $this->state(fn () => [
            'fecha_inicio' => now()->subMonths(2)->toDateString(),
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);
    }
}
