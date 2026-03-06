<?php

namespace App\Services\Crm;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Crm\CrmTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RenewalReactivationService
{
    /**
     * Clientes con membresía por vencer en los próximos X días (7, 3 o 1).
     */
    public function getRenewals(int $days = 7): Collection
    {
        $from = now()->toDateString();
        $to = now()->addDays($days)->toDateString();

        return ClienteMembresia::query()
            ->where('estado', 'activa')
            ->whereBetween('fecha_fin', [$from, $to])
            ->with(['cliente', 'membresia'])
            ->orderBy('fecha_fin')
            ->get();
    }

    /**
     * Clientes con membresía vencida hace X días (15, 30 o 60).
     */
    public function getReactivation(int $vencidosDias = 30): Collection
    {
        $fecha = now()->subDays($vencidosDias)->toDateString();

        return ClienteMembresia::query()
            ->where('estado', 'vencida')
            ->where('fecha_fin', '<=', $fecha)
            ->with(['cliente', 'membresia'])
            ->orderBy('fecha_fin', 'desc')
            ->get()
            ->unique('cliente_id')
            ->values();
    }

    /**
     * Genera tareas de renovación para asesores (si no tienen seguimiento reciente).
     * Se llama desde un Command/Job.
     */
    public function generateRenewalTasks(int $daysAhead = 7, ?int $assignedTo = null): int
    {
        $renewals = $this->getRenewals($daysAhead);
        $count = 0;
        $userId = $assignedTo ?? auth()->id();

        foreach ($renewals as $cm) {
            $cliente = $cm->cliente;
            if (!$cliente) {
                continue;
            }
            $hasRecentTask = CrmTask::where('cliente_id', $cliente->id)
                ->where('fecha_hora_programada', '>=', now()->subDays(3))
                ->exists();
            if ($hasRecentTask) {
                continue;
            }
            CrmTask::create([
                'cliente_id' => $cliente->id,
                'tipo' => 'follow_up',
                'fecha_hora_programada' => now()->addDay(),
                'prioridad' => $daysAhead <= 1 ? 'high' : 'medium',
                'estado' => 'pending',
                'assigned_to' => $userId,
                'notas' => 'Renovación: membresía vence ' . $cm->fecha_fin->format('d/m/Y'),
            ]);
            $count++;
        }

        return $count;
    }
}
