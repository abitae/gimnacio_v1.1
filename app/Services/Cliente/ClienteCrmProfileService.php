<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteCrmSummary;
use App\Models\Core\Cliente;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmTask;
use App\Models\Crm\Lead;

class ClienteCrmProfileService
{
    public function getSummary(int $clienteId): ClienteCrmSummary
    {
        $cliente = Cliente::query()->withCount('crmTags')->find($clienteId);

        $openTasksCount = CrmTask::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', 'pending')
            ->count();

        $lastActivity = CrmActivity::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_hora')
            ->first();

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
        );
    }
}
