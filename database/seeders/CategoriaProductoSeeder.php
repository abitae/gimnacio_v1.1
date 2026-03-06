<?php

namespace Database\Seeders;

use App\Models\Core\CategoriaProducto;
use Illuminate\Database\Seeder;

class CategoriaProductoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Suplementos',
                'descripcion' => 'Suplementos nutricionales y proteínas',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Ropa Deportiva',
                'descripcion' => 'Ropa y accesorios deportivos',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Accesorios',
                'descripcion' => 'Accesorios para entrenamiento',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Bebidas',
                'descripcion' => 'Bebidas energéticas e hidratantes',
                'estado' => 'activa',
            ],
        ];

        foreach ($categorias as $categoria) {
            CategoriaProducto::firstOrCreate(
                ['nombre' => $categoria['nombre']],
                $categoria
            );
        }
    }
}
