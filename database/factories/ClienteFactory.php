<?php

namespace Database\Factories;

use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    public function definition(): array
    {
        $tipoDocumento = fake()->randomElement(['DNI', 'CE']);

        return [
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $tipoDocumento === 'DNI'
                ? fake()->unique()->numerify('########')
                : 'CE' . fake()->unique()->numerify('######'),
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName() . ' ' . fake()->lastName(),
            'telefono' => '9' . fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->address(),
            'ocupacion' => fake()->jobTitle(),
            'fecha_nacimiento' => fake()->dateTimeBetween('-65 years', '-18 years')->format('Y-m-d'),
            'lugar_nacimiento' => fake()->city(),
            'estado_civil' => fake()->randomElement(['soltero', 'casado', 'divorciado', 'viudo']),
            'numero_hijos' => fake()->numberBetween(0, 4),
            'placa_carro' => fake()->optional()->bothify('???-###'),
            'estado_cliente' => 'activo',
            'foto' => null,
            'sexo' => fake()->randomElement(['masculino', 'femenino']),
            'datos_salud' => [
                'enfermedades' => fake()->optional()->sentence(3),
                'alergias' => fake()->optional()->word(),
                'medicacion' => fake()->optional()->sentence(2),
                'lesiones' => fake()->optional()->sentence(2),
            ],
            'datos_emergencia' => [
                'nombre_contacto' => fake()->name(),
                'telefono_contacto' => '9' . fake()->numerify('########'),
                'relacion' => fake()->randomElement(['Padre', 'Madre', 'Hermano', 'Pareja', 'Amigo']),
            ],
            'consentimientos' => [
                'uso_imagen' => fake()->boolean(75),
                'tratamiento_datos' => true,
                'fecha_consentimiento' => now()->toDateString(),
            ],
            'created_by' => User::factory(),
            'updated_by' => null,
            'biotime_state' => false,
            'biotime_update' => false,
            'trainer_user_id' => null,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado_cliente' => 'inactivo']);
    }
}
