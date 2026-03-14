<?php

namespace Database\Factories;

use App\Models\Core\CategoriaProducto;
use App\Models\Core\Producto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Producto>
 */
class ProductoFactory extends Factory
{
    protected $model = Producto::class;

    public function definition(): array
    {
        return [
            'codigo' => 'PROD-' . fake()->unique()->numerify('####'),
            'nombre' => fake()->words(3, true),
            'descripcion' => fake()->sentence(),
            'categoria_id' => CategoriaProducto::factory(),
            'precio_venta' => fake()->randomFloat(2, 5, 250),
            'precio_compra' => fake()->randomFloat(2, 1, 180),
            'stock_actual' => fake()->numberBetween(0, 150),
            'stock_minimo' => fake()->numberBetween(1, 20),
            'unidad_medida' => fake()->randomElement(['unidad', 'par', 'caja']),
            'imagen' => null,
            'estado' => 'activo',
        ];
    }

    public function sinStock(): static
    {
        return $this->state(fn () => ['stock_actual' => 0]);
    }
}
