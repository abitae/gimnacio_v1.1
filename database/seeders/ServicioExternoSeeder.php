<?php

namespace Database\Seeders;

use App\Models\Core\CategoriaServicio;
use App\Models\Core\ServicioExterno;
use Illuminate\Database\Seeder;

class ServicioExternoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categoriaMasajes = CategoriaServicio::where('nombre', 'Masajes')->first();
        $categoriaNutricion = CategoriaServicio::where('nombre', 'Nutrición')->first();
        $categoriaFisio = CategoriaServicio::where('nombre', 'Fisioterapia')->first();
        $categoriaEvaluacion = CategoriaServicio::where('nombre', 'Evaluación Física')->first();

        $servicios = [
            // Masajes
            [
                'codigo' => 'MAS-001',
                'nombre' => 'Masaje Relajante',
                'descripcion' => 'Masaje relajante de cuerpo completo',
                'categoria_id' => $categoriaMasajes?->id,
                'precio' => 80.00,
                'duracion_minutos' => 60,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'MAS-002',
                'nombre' => 'Masaje Deportivo',
                'descripcion' => 'Masaje para recuperación muscular',
                'categoria_id' => $categoriaMasajes?->id,
                'precio' => 90.00,
                'duracion_minutos' => 60,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'MAS-003',
                'nombre' => 'Masaje Terapéutico',
                'descripcion' => 'Masaje para aliviar tensiones y dolores',
                'categoria_id' => $categoriaMasajes?->id,
                'precio' => 100.00,
                'duracion_minutos' => 75,
                'estado' => 'activo',
            ],
            // Nutrición
            [
                'codigo' => 'NUT-001',
                'nombre' => 'Consulta Nutricional',
                'descripcion' => 'Consulta inicial con nutricionista',
                'categoria_id' => $categoriaNutricion?->id,
                'precio' => 120.00,
                'duracion_minutos' => 60,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'NUT-002',
                'nombre' => 'Plan Nutricional Personalizado',
                'descripcion' => 'Elaboración de plan nutricional personalizado',
                'categoria_id' => $categoriaNutricion?->id,
                'precio' => 150.00,
                'duracion_minutos' => 90,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'NUT-003',
                'nombre' => 'Seguimiento Nutricional',
                'descripcion' => 'Consulta de seguimiento nutricional',
                'categoria_id' => $categoriaNutricion?->id,
                'precio' => 80.00,
                'duracion_minutos' => 30,
                'estado' => 'activo',
            ],
            // Fisioterapia
            [
                'codigo' => 'FIS-001',
                'nombre' => 'Sesión de Fisioterapia',
                'descripcion' => 'Sesión individual de fisioterapia',
                'categoria_id' => $categoriaFisio?->id,
                'precio' => 100.00,
                'duracion_minutos' => 60,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'FIS-002',
                'nombre' => 'Rehabilitación Deportiva',
                'descripcion' => 'Tratamiento de rehabilitación para lesiones deportivas',
                'categoria_id' => $categoriaFisio?->id,
                'precio' => 120.00,
                'duracion_minutos' => 60,
                'estado' => 'activo',
            ],
            // Evaluación Física
            [
                'codigo' => 'EVA-001',
                'nombre' => 'Evaluación de Composición Corporal',
                'descripcion' => 'Análisis de composición corporal con báscula de bioimpedancia',
                'categoria_id' => $categoriaEvaluacion?->id,
                'precio' => 50.00,
                'duracion_minutos' => 30,
                'estado' => 'activo',
            ],
            [
                'codigo' => 'EVA-002',
                'nombre' => 'Evaluación Física Completa',
                'descripcion' => 'Evaluación física completa con pruebas de fuerza y resistencia',
                'categoria_id' => $categoriaEvaluacion?->id,
                'precio' => 80.00,
                'duracion_minutos' => 60,
                'estado' => 'activo',
            ],
        ];

        foreach ($servicios as $servicio) {
            ServicioExterno::create($servicio);
        }
    }
}
