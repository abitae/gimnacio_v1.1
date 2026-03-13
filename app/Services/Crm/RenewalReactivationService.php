<?php

namespace App\Services\Crm;

use App\Models\Core\ClienteMembresia;
use App\Models\Crm\CrmTask;
use App\Models\User;
use Illuminate\Support\Collection;

class RenewalReactivationService
{
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

    public function generateRenewalTasks(int $daysAhead = 7, ?int $assignedTo = null): int
    {
        $renewals = $this->getRenewals($daysAhead);
        $count = 0;
        $userId = $this->resolveAutomationUserId($assignedTo);

        foreach ($renewals as $cm) {
            $cliente = $cm->cliente;
            if (! $cliente) {
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
                'created_by' => $userId,
                'notas' => 'Renovacion: membresia vence ' . $cm->fecha_fin->format('d/m/Y'),
            ]);
            $count++;
        }

        return $count;
    }

    protected function resolveAutomationUserId(?int $assignedTo = null): int
    {
        if ($assignedTo) {
            return $assignedTo;
        }

        $configuredUserId = (int) config('crm.automation_user_id', 0);
        if ($configuredUserId > 0 && User::whereKey($configuredUserId)->exists()) {
            return $configuredUserId;
        }

        $fallbackUserId = User::query()->orderBy('id')->value('id');
        if ($fallbackUserId) {
            return (int) $fallbackUserId;
        }

        throw new \RuntimeException('No existe un usuario disponible para asignar tareas automaticas del CRM.');
    }
}
