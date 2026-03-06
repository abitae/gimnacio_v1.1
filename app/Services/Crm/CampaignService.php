<?php

namespace App\Services\Crm;

use App\Models\Crm\Campaign;
use App\Models\Crm\CampaignTarget;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    public function __construct(
        protected RenewalReactivationService $renewalService
    ) {}

    public function paginate(int $perPage = 15)
    {
        return Campaign::with('createdBy')->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    public function find(int $id): ?Campaign
    {
        return Campaign::with(['targets.lead', 'targets.cliente', 'targets.assignedTo'])->find($id);
    }

    public function create(array $data): Campaign
    {
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        return Campaign::create($data);
    }

    public function update(Campaign $campaign, array $data): Campaign
    {
        $campaign->update($data);
        return $campaign->fresh();
    }

    /**
     * Generar targets desde filtros de renovación/reactivación o desde una lista de cliente_ids.
     * filtros: { tipo: 'renovacion'|'reactivacion', dias_renovacion?: 7|3|1, vencidos_dias?: 15|30|60, cliente_ids?: [1,2,...] }
     */
    public function generateTargets(Campaign $campaign, array $filtros, ?int $assignedTo = null): int
    {
        $targets = [];
        if (!empty($filtros['cliente_ids'])) {
            foreach ($filtros['cliente_ids'] as $clienteId) {
                $targets[] = [
                    'campaign_id' => $campaign->id,
                    'cliente_id' => $clienteId,
                    'lead_id' => null,
                    'assigned_to' => $assignedTo,
                    'estado' => 'pending',
                ];
            }
        } else {
            $tipo = $filtros['tipo'] ?? 'renovacion';
            if ($tipo === 'renovacion') {
                $days = $filtros['dias_renovacion'] ?? 7;
                $items = $this->renewalService->getRenewals($days);
                foreach ($items as $cm) {
                    if ($cm->cliente_id) {
                        $targets[] = [
                            'campaign_id' => $campaign->id,
                            'cliente_id' => $cm->cliente_id,
                            'lead_id' => null,
                            'assigned_to' => $assignedTo,
                            'estado' => 'pending',
                        ];
                    }
                }
            } else {
                $vencidos = $filtros['vencidos_dias'] ?? 30;
                $items = $this->renewalService->getReactivation($vencidos);
                foreach ($items as $cm) {
                    $targets[] = [
                        'campaign_id' => $campaign->id,
                        'cliente_id' => $cm->cliente_id,
                        'lead_id' => null,
                        'assigned_to' => $assignedTo,
                        'estado' => 'pending',
                    ];
                }
            }
        }
        $unique = collect($targets)->unique(fn ($t) => ($t['cliente_id'] ?? '') . '-' . ($t['lead_id'] ?? '') . '-' . $t['campaign_id'])->values()->all();
        foreach ($unique as $t) {
            CampaignTarget::firstOrCreate(
                [
                    'campaign_id' => $campaign->id,
                    'cliente_id' => $t['cliente_id'] ?? null,
                    'lead_id' => $t['lead_id'] ?? null,
                ],
                $t
            );
        }
        return count($unique);
    }

    public function updateTargetStatus(CampaignTarget $target, string $estado): CampaignTarget
    {
        $target->update([
            'estado' => $estado,
            'last_activity_at' => in_array($estado, ['contacted', 'won', 'lost'], true) ? now() : $target->last_activity_at,
        ]);
        return $target->fresh();
    }

    public function assignTarget(CampaignTarget $target, ?int $userId): CampaignTarget
    {
        $target->update(['assigned_to' => $userId]);
        return $target->fresh();
    }
}
