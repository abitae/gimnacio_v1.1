<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmTask;
use App\Models\Crm\Deal;
use App\Models\Crm\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class CrmReportService
{
    /**
     * Reporte de conversión: leads creados, contactados, convertidos, tasa, tiempo promedio cierre.
     */
    public function reportConversion(?string $from, ?string $to): array
    {
        $q = Lead::query();
        if ($from) {
            $q->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $q->whereDate('created_at', '<=', $to);
        }
        $total = (clone $q)->count();
        $contactados = (clone $q)->whereIn('estado', ['contactado', 'interesado', 'agendo_visita', 'visito', 'negociacion'])->count();
        $convertidos = (clone $q)->where('estado', 'convertido')->count();
        $ganados = (clone $q)->where('estado', 'ganado')->count();
        $perdidos = (clone $q)->whereIn('estado', ['perdido', 'no_responde'])->count();

        $tasaConversion = $total > 0 ? round(($convertidos / $total) * 100, 2) : 0;

        $avgDays = Lead::query()
            ->where('estado', 'convertido')
            ->whereNotNull('cliente_id')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->selectRaw('AVG(DATEDIFF(updated_at, created_at)) as avg_days')
            ->value('avg_days');

        return [
            'total_leads' => $total,
            'contactados' => $contactados,
            'convertidos' => $convertidos,
            'ganados' => $ganados,
            'perdidos' => $perdidos,
            'tasa_conversion' => $tasaConversion,
            'tiempo_promedio_cierre_dias' => $avgDays !== null ? round((float) $avgDays, 1) : null,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * Por asesor: leads asignados, actividades, tareas cumplidas/vencidas, ventas ganadas (monto).
     * Usa consultas agregadas por user_id para evitar N+1.
     */
    public function reportByAdvisor(?string $from, ?string $to): Collection
    {
        $leadQ = Lead::query()->selectRaw('assigned_to as user_id, count(*) as leads_count')
            ->whereNotNull('assigned_to');
        $from && $leadQ->whereDate('created_at', '>=', $from);
        $to && $leadQ->whereDate('created_at', '<=', $to);
        $leadsByUser = $leadQ->groupBy('assigned_to')->pluck('leads_count', 'user_id');

        $activitiesQ = CrmActivity::query()->selectRaw('user_id, count(*) as total')
            ->whereNotNull('user_id');
        $from && $activitiesQ->whereDate('fecha_hora', '>=', $from);
        $to && $activitiesQ->whereDate('fecha_hora', '<=', $to);
        $activitiesByUser = $activitiesQ->groupBy('user_id')->pluck('total', 'user_id');

        $tasksDoneQ = CrmTask::query()->selectRaw('assigned_to as user_id, count(*) as total')
            ->where('estado', 'done')->whereNotNull('assigned_to');
        $from && $tasksDoneQ->whereDate('completed_at', '>=', $from);
        $to && $tasksDoneQ->whereDate('completed_at', '<=', $to);
        $tasksDoneByUser = $tasksDoneQ->groupBy('assigned_to')->pluck('total', 'user_id');

        $tasksOverdueQ = CrmTask::query()->selectRaw('assigned_to as user_id, count(*) as total')
            ->where('estado', 'overdue')->whereNotNull('assigned_to');
        $from && $tasksOverdueQ->whereDate('fecha_hora_programada', '>=', $from);
        $to && $tasksOverdueQ->whereDate('fecha_hora_programada', '<=', $to);
        $tasksOverdueByUser = $tasksOverdueQ->groupBy('assigned_to')->pluck('total', 'user_id');

        $dealsWonQ = Deal::query()->selectRaw('assigned_to as user_id, count(*) as deals_count, coalesce(sum(precio_objetivo), 0) as monto')
            ->where('estado', 'won')->whereNotNull('assigned_to');
        $from && $dealsWonQ->whereDate('updated_at', '>=', $from);
        $to && $dealsWonQ->whereDate('updated_at', '<=', $to);
        $dealsWonByUser = $dealsWonQ->groupBy('assigned_to')->get()->keyBy('user_id');

        $userIds = collect()
            ->merge($leadsByUser->keys())
            ->merge($activitiesByUser->keys())
            ->merge($tasksDoneByUser->keys())
            ->merge($tasksOverdueByUser->keys())
            ->merge($dealsWonByUser->keys())
            ->unique()
            ->filter()
            ->values();

        $users = User::whereIn('id', $userIds)->get()->keyBy('id');

        return $userIds->map(function ($uid) use ($leadsByUser, $activitiesByUser, $tasksDoneByUser, $tasksOverdueByUser, $dealsWonByUser, $users) {
            $dw = $dealsWonByUser->get($uid);
            return [
                'user_id' => $uid,
                'user_name' => $users->get($uid)?->name ?? '—',
                'leads_count' => (int) ($leadsByUser->get($uid) ?? 0),
                'activities_count' => (int) ($activitiesByUser->get($uid) ?? 0),
                'tasks_done' => (int) ($tasksDoneByUser->get($uid) ?? 0),
                'tasks_overdue' => (int) ($tasksOverdueByUser->get($uid) ?? 0),
                'deals_won_count' => $dw ? (int) $dw->deals_count : 0,
                'monto_ventas' => $dw ? (float) $dw->monto : 0,
            ];
        })->values();
    }

    /**
     * Por canal: leads por canal_origen.
     */
    public function reportByChannel(?string $from, ?string $to): Collection
    {
        $q = Lead::query()->selectRaw("coalesce(canal_origen, 'Sin canal') as canal, count(*) as total");
        $from && $q->whereDate('created_at', '>=', $from);
        $to && $q->whereDate('created_at', '<=', $to);
        $q->groupByRaw("coalesce(canal_origen, 'Sin canal')");
        return $q->get()->map(fn ($r) => ['canal' => $r->canal, 'total' => $r->total]);
    }
}
