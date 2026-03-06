<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = [
            ['nombre' => 'Press de banca', 'grupo_muscular_principal' => 'Pecho', 'tipo' => 'fuerza', 'nivel' => 'Intermedio', 'equipamiento' => 'Barra', 'estado' => 'activo'],
            ['nombre' => 'Sentadilla trasera', 'grupo_muscular_principal' => 'Cuádriceps', 'tipo' => 'fuerza', 'nivel' => 'Intermedio', 'equipamiento' => 'Barra', 'estado' => 'activo'],
            ['nombre' => 'Peso muerto convencional', 'grupo_muscular_principal' => 'Espalda', 'tipo' => 'fuerza', 'nivel' => 'Intermedio', 'equipamiento' => 'Barra', 'estado' => 'activo'],
            ['nombre' => 'Remo con barra', 'grupo_muscular_principal' => 'Espalda', 'tipo' => 'fuerza', 'nivel' => 'Principiante', 'equipamiento' => 'Barra', 'estado' => 'activo'],
            ['nombre' => 'Press militar', 'grupo_muscular_principal' => 'Hombros', 'tipo' => 'fuerza', 'nivel' => 'Intermedio', 'equipamiento' => 'Barra', 'estado' => 'activo'],
            ['nombre' => 'Curl de bíceps', 'grupo_muscular_principal' => 'Bíceps', 'tipo' => 'hipertrofia', 'nivel' => 'Principiante', 'equipamiento' => 'Mancuernas', 'estado' => 'activo'],
            ['nombre' => 'Extensión de tríceps en polea', 'grupo_muscular_principal' => 'Tríceps', 'tipo' => 'hipertrofia', 'nivel' => 'Principiante', 'equipamiento' => 'Polea', 'estado' => 'activo'],
            ['nombre' => 'Zancadas', 'grupo_muscular_principal' => 'Cuádriceps', 'tipo' => 'fuerza', 'nivel' => 'Principiante', 'equipamiento' => 'Mancuernas', 'estado' => 'activo'],
            ['nombre' => 'Plancha abdominal', 'grupo_muscular_principal' => 'Core', 'tipo' => 'fuerza', 'nivel' => 'Principiante', 'equipamiento' => 'Cuerpo libre', 'estado' => 'activo'],
            ['nombre' => 'Correr en cinta', 'grupo_muscular_principal' => 'Piernas', 'tipo' => 'cardio', 'nivel' => 'Principiante', 'equipamiento' => 'Cinta', 'estado' => 'activo'],
            ['nombre' => 'Estiramiento de isquiotibiales', 'grupo_muscular_principal' => 'Piernas', 'tipo' => 'estiramiento', 'nivel' => 'Principiante', 'equipamiento' => 'Cuerpo libre', 'estado' => 'activo'],
        ];
        foreach ($exercises as $ex) {
            Exercise::firstOrCreate(
                ['nombre' => $ex['nombre']],
                array_merge($ex, ['musculos_secundarios' => []])
            );
        }
    }
}
