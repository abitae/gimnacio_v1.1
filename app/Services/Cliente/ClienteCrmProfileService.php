<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteCrmSummary;
use App\Models\Core\Cliente;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmTask;
use App\Models\Crm\Deal;
use App\Models\Crm\Lead;

class ClienteCrmProfileService
{
    public function getSummary(int $clienteId): ClienteCrmSummary
    {
        $cliente = Cliente::query()->with('asesorCrm')->withCount('crmTags')->find($clienteId);

        $openTasksCount = CrmTask::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', 'pending')
            ->count();

        $openDeals = Deal::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', 'open')
            ->with('membresia')
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Deal $d) => [
                'id' => $d->id,
                'membresia_nombre' => $d->membresia?->nombre,
                'precio_objetivo' => $d->precio_objetivo,
                'probabilidad' => $d->probabilidad,
                'fecha_estimada_cierre' => $d->fecha_estimada_cierre,
            ])->all();

        $openDealsCount = count($openDeals);

        $lastActivity = CrmActivity::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_hora')
            ->first();

        $recentActivities = CrmActivity::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_hora')
            ->limit(5)
            ->get()
            ->map(fn (CrmActivity $a) => [
                'id' => $a->id,
                'tipo' => $a->tipo,
                'tipo_label' => $a->tipo_label,
                'observaciones' => $a->observaciones,
                'fecha_hora' => $a->fecha_hora,
            ])->all();

        $pendingTasks = CrmTask::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', 'pending')
            ->orderBy('fecha_hora_programada')
            ->limit(5)
            ->get()
            ->map(fn (CrmTask $t) => [
                'id' => $t->id,
                'tipo' => $t->tipo,
                'tipo_label' => $t->tipo_label,
                'fecha_hora_programada' => $t->fecha_hora_programada,
                'prioridad' => $t->prioridad,
            ])->all();

        $linkedLead = Lead::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('updated_at')
            ->first();

        return new ClienteCrmSummary(
            tagsCount: (int) ($cliente?->crm_tags_count ?? 0),
            openTasksCount: $openTasksCount,
            lastActivity: $lastActivity ? [
                'id' => $lastActivity->id,
                'tipo' => $lastActivity->tipo,
                'titulo' => $lastActivity->observaciones,
                'fecha_hora' => $lastActivity->fecha_hora,
            ] : null,
            linkedLead: $linkedLead ? [
                'id' => $linkedLead->id,
                'nombre' => trim(($linkedLead->nombres ?? '').' '.($linkedLead->apellidos ?? '')),
                'estado' => $linkedLead->estado,
            ] : null,
            asesorCrmId: $cliente?->asesor_crm_id,
            asesorCrmNombre: $cliente?->asesorCrm?->name,
            openDealsCount: $openDealsCount,
            recentActivities: $recentActivities,
            pendingTasks: $pendingTasks,
            openDeals: $openDeals,
        );
    }
}
