<?php

namespace Database\Factories;

use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    protected $model = Cliente::class;

    /** Secuencia por proceso: evita colisiones con ClienteSeeder (DNI ≥ 10000000, CE ≥ CE100000) y con la propia BD. */
    protected static int $documentoSecuencia = 0;

    public function definition(): array
    {
        self::$documentoSecuencia++;

        $tipoDocumento = fake()->randomElement(['DNI', 'CE']);

        // Rangos disjuntos de ClienteSeeder: DNI 00000001–00999999, CE000001–CE099999
        if ($tipoDocumento === 'DNI') {
            $n = (self::$documentoSecuencia % 999_999) + 1;
            $numeroDocumento = str_pad((string) $n, 8, '0', STR_PAD_LEFT);
        } else {
            $n = (self::$documentoSecuencia % 99_999) + 1;
            $numeroDocumento = 'CE'.str_pad((string) $n, 6, '0', STR_PAD_LEFT);
        }

        return [
            'codigo' => null,
            'tipo_documento' => $tipoDocumento,
            'numero_documento' => $numeroDocumento,
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'telefono' => '9'.fake()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'direccion' => fake()->address(),
            'ocupacion' => Str::limit((string) fake()->jobTitle(), 80, ''),
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
                'telefono_contacto' => '9'.fake()->numerify('########'),
                'relacion' => fake()->randomElement(['Padre', 'Madre', 'Hermano', 'Pareja', 'Amigo']),
            ],
            'consentimientos' => [
                'uso_imagen' => fake()->boolean(75),
                'tratamiento_datos' => true,
                'fecha_consentimiento' => now()->toDateString(),
            ],
            'created_by' => User::factory(),
            'updated_by' => null,
            'trainer_user_id' => null,
        ];
    }

    public function inactivo(): static
    {
        return $this->state(fn () => ['estado_cliente' => 'inactivo']);
    }
}
