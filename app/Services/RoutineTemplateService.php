<?php

namespace App\Services;

use App\Models\RoutineTemplate;
use App\Models\RoutineTemplateDay;
use App\Models\RoutineTemplateDayExercise;
use App\Models\User;

class RoutineTemplateService
{
    /**
     * Clona una plantilla de rutina: nueva RoutineTemplate + días + ejercicios por día.
     * El nuevo template queda en estado borrador y sin created_by si no se pasa.
     */
    public function clone(RoutineTemplate $template, ?User $createdBy = null): RoutineTemplate
    {
        $newTemplate = $template->replicate();
        $newTemplate->nombre = $template->nombre . ' (copia)';
        $newTemplate->estado = 'borrador';
        $newTemplate->created_by = $createdBy?->id;
        $newTemplate->save();

        foreach ($template->days as $day) {
            $newDay = $day->replicate();
            $newDay->routine_template_id = $newTemplate->id;
            $newDay->setRelation('routineTemplate', $newTemplate);
            $newDay->save();

            foreach ($day->exercises as $dayEx) {
                $newDayEx = $dayEx->replicate();
                $newDayEx->routine_template_day_id = $newDay->id;
                $newDayEx->setRelation('routineTemplateDay', $newDay);
                $newDayEx->save();
            }
        }

        return $newTemplate->load('days.exercises');
    }
}
