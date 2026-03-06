<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\RoutineTemplate;
use App\Models\RoutineTemplateDay;
use App\Models\RoutineTemplateDayExercise;
use Illuminate\Database\Seeder;

class RoutineTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $exercises = Exercise::where('estado', 'activo')->orderBy('id')->get();
        if ($exercises->count() < 3) {
            return;
        }
        $template = RoutineTemplate::firstOrCreate(
            ['nombre' => 'Rutina full body 3 días'],
            [
                'objetivo' => 'Fuerza e hipertrofia',
                'nivel' => 'Principiante',
                'duracion_semanas' => 8,
                'frecuencia_dias_semana' => 3,
                'descripcion' => 'Rutina de cuerpo completo para 3 días por semana.',
                'tags' => ['full body', 'principiante'],
                'estado' => 'activa',
            ]
        );
        if ($template->days()->count() > 0) {
            return;
        }
        $day1 = RoutineTemplateDay::create(['routine_template_id' => $template->id, 'nombre' => 'Día 1', 'orden' => 0]);
        foreach ([0, 1, 2] as $i) {
            if (isset($exercises[$i])) {
                RoutineTemplateDayExercise::create([
                    'routine_template_day_id' => $day1->id,
                    'exercise_id' => $exercises[$i]->id,
                    'series' => 3,
                    'repeticiones' => '8-12',
                    'descanso_segundos' => 90,
                    'metodo' => 'normal',
                    'orden' => $i,
                ]);
            }
        }
        $day2 = RoutineTemplateDay::create(['routine_template_id' => $template->id, 'nombre' => 'Día 2', 'orden' => 1]);
        foreach ([3, 4, 5] as $i) {
            if (isset($exercises[$i])) {
                RoutineTemplateDayExercise::create([
                    'routine_template_day_id' => $day2->id,
                    'exercise_id' => $exercises[$i]->id,
                    'series' => 3,
                    'repeticiones' => '8-12',
                    'descanso_segundos' => 90,
                    'metodo' => 'normal',
                    'orden' => $i - 3,
                ]);
            }
        }
        $template2 = RoutineTemplate::firstOrCreate(
            ['nombre' => 'Rutina push/pull 4 días'],
            [
                'objetivo' => 'Hipertrofia',
                'nivel' => 'Intermedio',
                'duracion_semanas' => 12,
                'frecuencia_dias_semana' => 4,
                'descripcion' => 'Dividida en push y pull.',
                'tags' => ['push', 'pull', 'intermedio'],
                'estado' => 'borrador',
            ]
        );
        if ($template2->days()->count() > 0) {
            return;
        }
        RoutineTemplateDay::create(['routine_template_id' => $template2->id, 'nombre' => 'Push', 'orden' => 0]);
        RoutineTemplateDay::create(['routine_template_id' => $template2->id, 'nombre' => 'Pull', 'orden' => 1]);
    }
}
