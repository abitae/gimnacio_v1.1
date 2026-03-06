<?php

namespace Database\Seeders;

use App\Models\Core\CategoriaProducto;
use App\Models\Core\Producto;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriaSuplementos = CategoriaProducto::where('nombre', 'Suplementos')->first();
        $categoriaRopa = CategoriaProducto::where('nombre', 'Ropa Deportiva')->first();
        $categoriaAccesorios = CategoriaProducto::where('nombre', 'Accesorios')->first();
        $categoriaBebidas = CategoriaProducto::where('nombre', 'Bebidas')->first();

        $productos = [
            // Suplementos
            [
                'codigo' => 'SUP-001',
                'nombre' => 'Proteína Whey 1kg',
                'descripcion' => 'Proteína de suero de leche sabor chocolate',
                'categoria_id' => $categoriaSuplementos?->id,
                'precio_venta' => 85.00,
                'precio_compra' => 60.00,
                'stock_actual' => 25,
                'stock_minimo' => 5,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'SUP-002',
                'nombre' => 'Creatina Monohidrato 300g',
                'descripcion' => 'Creatina monohidratada en polvo',
                'categoria_id' => $categoriaSuplementos?->id,
                'precio_venta' => 45.00,
                'precio_compra' => 30.00,
                'stock_actual' => 15,
                'stock_minimo' => 3,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'SUP-003',
                'nombre' => 'BCAA 300g',
                'descripcion' => 'Aminoácidos de cadena ramificada',
                'categoria_id' => $categoriaSuplementos?->id,
                'precio_venta' => 65.00,
                'precio_compra' => 45.00,
                'stock_actual' => 20,
                'stock_minimo' => 5,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'SUP-004',
                'nombre' => 'Pre-entreno 300g',
                'descripcion' => 'Suplemento pre-entrenamiento con cafeína',
                'categoria_id' => $categoriaSuplementos?->id,
                'precio_venta' => 75.00,
                'precio_compra' => 50.00,
                'stock_actual' => 12,
                'stock_minimo' => 3,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            // Ropa Deportiva
            [
                'codigo' => 'ROP-001',
                'nombre' => 'Camiseta Deportiva',
                'descripcion' => 'Camiseta de algodón transpirable',
                'categoria_id' => $categoriaRopa?->id,
                'precio_venta' => 35.00,
                'precio_compra' => 20.00,
                'stock_actual' => 30,
                'stock_minimo' => 10,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'ROP-002',
                'nombre' => 'Short Deportivo',
                'descripcion' => 'Short de entrenamiento',
                'categoria_id' => $categoriaRopa?->id,
                'precio_venta' => 28.00,
                'precio_compra' => 15.00,
                'stock_actual' => 25,
                'stock_minimo' => 8,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'ROP-003',
                'nombre' => 'Toalla Deportiva',
                'descripcion' => 'Toalla de microfibra',
                'categoria_id' => $categoriaRopa?->id,
                'precio_venta' => 22.00,
                'precio_compra' => 12.00,
                'stock_actual' => 40,
                'stock_minimo' => 15,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            // Accesorios
            [
                'codigo' => 'ACC-001',
                'nombre' => 'Guantes de Gimnasio',
                'descripcion' => 'Guantes para levantamiento de pesas',
                'categoria_id' => $categoriaAccesorios?->id,
                'precio_venta' => 45.00,
                'precio_compra' => 25.00,
                'stock_actual' => 18,
                'stock_minimo' => 5,
                'unidad_medida' => 'par',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'ACC-002',
                'nombre' => 'Cinturón de Levantamiento',
                'descripcion' => 'Cinturón de cuero para powerlifting',
                'categoria_id' => $categoriaAccesorios?->id,
                'precio_venta' => 120.00,
                'precio_compra' => 80.00,
                'stock_actual' => 8,
                'stock_minimo' => 2,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'ACC-003',
                'nombre' => 'Botella Deportiva',
                'descripcion' => 'Botella de acero inoxidable 750ml',
                'categoria_id' => $categoriaAccesorios?->id,
                'precio_venta' => 30.00,
                'precio_compra' => 18.00,
                'stock_actual' => 35,
                'stock_minimo' => 10,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            // Bebidas
            [
                'codigo' => 'BEB-001',
                'nombre' => 'Agua Mineral 500ml',
                'descripcion' => 'Agua mineral embotellada',
                'categoria_id' => $categoriaBebidas?->id,
                'precio_venta' => 2.50,
                'precio_compra' => 1.20,
                'stock_actual' => 100,
                'stock_minimo' => 30,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'BEB-002',
                'nombre' => 'Bebida Energética 500ml',
                'descripcion' => 'Bebida energética con electrolitos',
                'categoria_id' => $categoriaBebidas?->id,
                'precio_venta' => 5.00,
                'precio_compra' => 2.50,
                'stock_actual' => 50,
                'stock_minimo' => 15,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            [
                'codigo' => 'BEB-003',
                'nombre' => 'Batido de Proteína',
                'descripcion' => 'Batido listo para beber',
                'categoria_id' => $categoriaBebidas?->id,
                'precio_venta' => 8.00,
                'precio_compra' => 4.50,
                'stock_actual' => 30,
                'stock_minimo' => 10,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
            // Producto con stock bajo para pruebas
            [
                'codigo' => 'SUP-005',
                'nombre' => 'Glutamina 300g',
                'descripcion' => 'Glutamina en polvo',
                'categoria_id' => $categoriaSuplementos?->id,
                'precio_venta' => 55.00,
                'precio_compra' => 35.00,
                'stock_actual' => 2,
                'stock_minimo' => 5,
                'unidad_medida' => 'unidad',
                'estado' => 'activo',
            ],
        ];

        foreach ($productos as $producto) {
            Producto::firstOrCreate(
                ['codigo' => $producto['codigo']],
                $producto
            );
        }
    }
}
