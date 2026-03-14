<?php

namespace Database\Factories;

use App\Models\Core\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement(['Efectivo', 'Transferencia', 'Tarjeta', 'Yape', 'Plin', 'QR']) . ' ' . fake()->unique()->numerify('##'),
            'descripcion' => fake()->sentence(),
            'requiere_numero_operacion' => fake()->boolean(70),
            'requiere_entidad' => fake()->boolean(50),
            'estado' => 'activo',
        ];
    }
}
