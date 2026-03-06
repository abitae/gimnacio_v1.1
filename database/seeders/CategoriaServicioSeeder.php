<?php

namespace Database\Seeders;

use App\Models\Core\CategoriaServicio;
use Illuminate\Database\Seeder;

class CategoriaServicioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            [
                'nombre' => 'Masajes',
                'descripcion' => 'Servicios de masajes terapéuticos y relajantes',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Nutrición',
                'descripcion' => 'Consultas y planes nutricionales',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Fisioterapia',
                'descripcion' => 'Tratamientos de fisioterapia y rehabilitación',
                'estado' => 'activa',
            ],
            [
                'nombre' => 'Evaluación Física',
                'descripcion' => 'Evaluaciones físicas y composición corporal',
                'estado' => 'activa',
            ],
        ];

        foreach ($categorias as $categoria) {
            CategoriaServicio::create($categoria);
        }
    }
}
