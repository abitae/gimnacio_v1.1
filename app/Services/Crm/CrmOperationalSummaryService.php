<?php

namespace App\Services\Crm;

use App\Models\Crm\Deal;
use App\Models\Crm\Lead;
use Carbon\Carbon;

class CrmOperationalSummaryService
{
    public function __construct(
        protected CrmTaskService $crmTaskService,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getSummary(?int $assignedTo = null): array
    {
        $userId = $assignedTo ?? auth()->id();
        $weekStart = Carbon::now()->startOfWeek();

        $leadsNuevosSemana = Lead::query()
            ->where('created_at', '>=', $weekStart)
            ->when($assignedTo, fn ($q) => $q->where('assigned_to', $assignedTo))
            ->count();

        $leadsConvertidos = Lead::query()
            ->where('estado', 'convertido')
            ->where('updated_at', '>=', $weekStart)
            ->count();

        $tasaConversion = $leadsNuevosSemana > 0
            ? round(($leadsConvertidos / $leadsNuevosSemana) * 100, 1)
            : 0.0;

        $dealsAbiertos = Deal::query()
            ->where('estado', 'open')
            ->when($assignedTo, fn ($q) => $q->where('assigned_to', $assignedTo))
            ->get();

        $myDay = $this->crmTaskService->getMyDay((int) $userId);

        return [
            'leads_nuevos_semana' => $leadsNuevosSemana,
            'leads_convertidos_semana' => $leadsConvertidos,
            'tasa_conversion_semana' => $tasaConversion,
            'deals_abiertos' => $dealsAbiertos->count(),
            'deals_valor_abierto' => round((float) $dealsAbiertos->sum('precio_objetivo'), 2),
            'tareas_vencidas' => count($myDay['overdue'] ?? []),
            'tareas_hoy' => count($myDay['today'] ?? []),
        ];
    }
}
