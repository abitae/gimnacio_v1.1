<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmTask;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CrmTaskService
{
    public function query(array $filters = []): Builder
    {
        $q = CrmTask::query()->with(['assignedTo', 'lead', 'cliente', 'deal']);

        if (isset($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'me') {
                $q->where('assigned_to', auth()->id());
            } else {
                $q->where('assigned_to', $filters['assigned_to']);
            }
        }
        if (!empty($filters['status'])) {
            $q->where('estado', $filters['status']);
        }
        if (!empty($filters['from'])) {
            $q->whereDate('fecha_hora_programada', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('fecha_hora_programada', '<=', $filters['to']);
        }

        return $q->orderBy('fecha_hora_programada');
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function getMyDay(int $userId): array
    {
        $today = now()->toDateString();
        $endNext7 = now()->addDays(7)->toDateString();

        $todayTasks = CrmTask::where('assigned_to', $userId)
            ->where('estado', 'pending')
            ->whereDate('fecha_hora_programada', $today)
            ->with(['lead', 'cliente'])
            ->orderBy('fecha_hora_programada')
            ->get();

        $overdueTasks = CrmTask::where('assigned_to', $userId)
            ->where('estado', 'pending')
            ->where('fecha_hora_programada', '<', now())
            ->with(['lead', 'cliente'])
            ->orderBy('fecha_hora_programada')
            ->get();

        $next7DaysTasks = CrmTask::where('assigned_to', $userId)
            ->where('estado', 'pending')
            ->whereDate('fecha_hora_programada', '>', $today)
            ->whereDate('fecha_hora_programada', '<=', $endNext7)
            ->with(['lead', 'cliente'])
            ->orderBy('fecha_hora_programada')
            ->get();

        return [
            'today' => $todayTasks,
            'overdue' => $overdueTasks,
            'next_7_days' => $next7DaysTasks,
        ];
    }

    public function create(array $data): CrmTask
    {
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['assigned_to'] = $data['assigned_to'] ?? auth()->id();
        $data['estado'] = $data['estado'] ?? 'pending';
        return CrmTask::create($data);
    }

    public function update(CrmTask $task, array $data): CrmTask
    {
        $task->update($data);
        return $task->fresh();
    }

    public function complete(CrmTask $task): CrmTask
    {
        $task->update([
            'estado' => 'done',
            'completed_at' => now(),
        ]);
        return $task->fresh();
    }

    public function reschedule(CrmTask $task, string $fechaHora): CrmTask
    {
        $task->update([
            'fecha_hora_programada' => $fechaHora,
            'estado' => 'pending',
        ]);
        return $task->fresh();
    }

    public function delete(CrmTask $task): bool
    {
        return $task->delete();
    }
}
