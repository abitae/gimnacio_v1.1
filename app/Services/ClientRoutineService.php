<?php

namespace App\Services;

use App\Models\ClientRoutine;
use App\Models\ClientRoutineDay;
use App\Models\ClientRoutineDayExercise;
use App\Models\Core\Cliente;
use App\Models\RoutineTemplate;
use App\Models\User;

class ClientRoutineService
{
    /**
     * Asigna una rutina base a un cliente: crea ClientRoutine y clona días y ejercicios del template.
     *
     * @param  array{fecha_inicio: string, fecha_fin?: string|null, objetivo_personal?: string|null, restricciones?: string|null, observaciones?: string|null}  $data
     */
    public function assignFromTemplate(Cliente $cliente, RoutineTemplate $template, User $trainer, array $data): ClientRoutine
    {
        $routine = new ClientRoutine;
        $routine->cliente_id = $cliente->id;
        $routine->routine_template_id = $template->id;
        $routine->trainer_user_id = $trainer->id;
        $routine->fecha_inicio = $data['fecha_inicio'] ?? now()->toDateString();
        $routine->fecha_fin = $data['fecha_fin'] ?? null;
        $routine->objetivo_personal = $data['objetivo_personal'] ?? null;
        $routine->restricciones = $data['restricciones'] ?? null;
        $routine->observaciones = $data['observaciones'] ?? null;
        $routine->estado = 'activa';
        $routine->save();

        $template->load('days.exercises');

        foreach ($template->days as $day) {
            $clientDay = new ClientRoutineDay;
            $clientDay->client_routine_id = $routine->id;
            $clientDay->nombre = $day->nombre;
            $clientDay->orden = $day->orden;
            $clientDay->save();

            foreach ($day->exercises as $dayEx) {
                $clientDayEx = new ClientRoutineDayExercise;
                $clientDayEx->client_routine_day_id = $clientDay->id;
                $clientDayEx->exercise_id = $dayEx->exercise_id;
                $clientDayEx->series = $dayEx->series;
                $clientDayEx->repeticiones = $dayEx->repeticiones;
                $clientDayEx->descanso_segundos = $dayEx->descanso_segundos;
                $clientDayEx->tempo = $dayEx->tempo;
                $clientDayEx->intensidad_rpe = $dayEx->intensidad_rpe;
                $clientDayEx->metodo = $dayEx->metodo;
                $clientDayEx->notas = $dayEx->notas;
                $clientDayEx->orden = $dayEx->orden;
                $clientDayEx->save();
            }
        }

        return $routine->load('days.exercises.exercise');
    }
}
