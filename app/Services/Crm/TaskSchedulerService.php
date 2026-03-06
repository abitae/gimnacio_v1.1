<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmTask;
use Illuminate\Support\Facades\Log;

class TaskSchedulerService
{
    /**
     * Marca como vencidas las tareas pendientes cuya fecha_hora_programada ya pasó.
     */
    public function markOverdueTasks(): int
    {
        $count = CrmTask::where('estado', 'pending')
            ->where('fecha_hora_programada', '<', now())
            ->update(['estado' => 'overdue']);

        if ($count > 0) {
            Log::info("CRM: {$count} tareas marcadas como vencidas.");
        }

        return $count;
    }
}
