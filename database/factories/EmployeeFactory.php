<?php

namespace Database\Factories;

use App\Models\Core\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'user_id' => null,
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName() . ' ' . fake()->lastName(),
            'documento' => fake()->unique()->numerify('########'),
            'cargo' => fake()->randomElement(['Recepción', 'Ventas', 'Trainer', 'Nutricionista', 'Mantenimiento']),
            'area' => fake()->randomElement(['Operaciones', 'Comercial', 'Bienestar', 'Administración']),
            'telefono' => fake()->optional()->numerify('9########'),
            'fecha_ingreso' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'estado' => 'activo',
        ];
    }

    public function vinculadoAUsuario(): static
    {
        return $this->state(fn () => ['user_id' => User::factory()]);
    }
}
