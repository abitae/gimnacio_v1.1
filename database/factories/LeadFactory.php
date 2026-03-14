<?php

namespace Database\Factories;

use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lead>
 */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'tipo_documento' => fake()->randomElement(['DNI', 'CE']),
            'numero_documento' => fake()->optional()->numerify('########'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName() . ' ' . fake()->lastName(),
            'telefono' => '9' . fake()->numerify('########'),
            'whatsapp' => '9' . fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->address(),
            'canal_origen' => fake()->randomElement(['Meta Ads', 'Referido', 'WhatsApp', 'Volante', 'Instagram']),
            'sede' => fake()->randomElement(['Principal', 'Norte', 'Sur']),
            'interes_principal' => fake()->randomElement(['Membresía', 'Clases', 'Nutrición', 'Crossfit']),
            'estado' => fake()->randomElement(Lead::ESTADOS),
            'stage_id' => CrmStage::factory(),
            'assigned_to' => null,
            'cliente_id' => null,
            'fecha_ultimo_contacto' => now()->subDays(fake()->numberBetween(0, 20)),
            'notas' => fake()->optional()->paragraph(),
            'created_by' => User::factory(),
        ];
    }

    public function convertido(): static
    {
        return $this->state(fn () => ['estado' => 'convertido']);
    }
}
