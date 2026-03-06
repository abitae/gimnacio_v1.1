<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Campaign;
use App\Models\Crm\CampaignTarget;
use App\Services\Crm\CampaignService;
use Livewire\Component;
use Livewire\WithPagination;

class CampaignDetailLive extends Component
{
    use FlashesToast, WithPagination;

    public int $campaignId;
    public $modalGenerar = false;
    public $filtroTipo = 'renovacion';
    public $filtroDias = '7';
    public $asignarUsuario = '';
    public $editingTargetId = null;
    public $targetEstado = 'pending';
    public $targetAssignedTo = '';
    /** @var array<int, string|int> Asignado por target: targetId => user_id */
    public $targetAssignments = [];
    /** @var array<int, string> Estado por target: targetId => estado */
    public $targetStatuses = [];

    protected CampaignService $campaignService;
    protected $paginationTheme = 'tailwind';

    public function boot(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function mount(int $campaign)
    {
        $this->authorize('crm.view');
        $this->campaignId = (int) $campaign;
    }

    public function getCampaignProperty(): ?Campaign
    {
        return $this->campaignService->find($this->campaignId);
    }

    public function openGenerarTargets()
    {
        $this->authorize('crm.create');
        $this->modalGenerar = true;
    }

    public function generarTargets()
    {
        $this->authorize('crm.create');
        $campaign = $this->getCampaignProperty();
        if (!$campaign) return;
        $filtros = [
            'tipo' => $this->filtroTipo,
            'dias_renovacion' => $this->filtroTipo === 'renovacion' ? (int) $this->filtroDias : null,
            'vencidos_dias' => $this->filtroTipo === 'reactivacion' ? (int) $this->filtroDias : null,
        ];
        $count = $this->campaignService->generateTargets(
            $campaign,
            $filtros,
            $this->asignarUsuario ? (int) $this->asignarUsuario : null
        );
        $this->modalGenerar = false;
        $this->flashToast('success', "Se generaron {$count} targets.");
    }

    public function updateTargetStatus(int $targetId, string $estado)
    {
        $this->authorize('crm.update');
        $target = CampaignTarget::find($targetId);
        if ($target && $target->campaign_id === $this->campaignId) {
            $this->campaignService->updateTargetStatus($target, $estado);
            $this->flashToast('success', 'Estado actualizado');
        }
    }

    public function assignTarget(int $targetId, $userId)
    {
        $this->authorize('crm.update');
        $target = CampaignTarget::find($targetId);
        if ($target && $target->campaign_id === $this->campaignId) {
            $this->campaignService->assignTarget($target, $userId ? (int) $userId : null);
            $this->flashToast('success', 'Asignado');
        }
    }

    public function updated($name, $value)
    {
        if (str_starts_with($name, 'targetAssignments.')) {
            $id = (int) substr($name, strlen('targetAssignments.'));
            $this->assignTarget($id, $value);
        }
        if (str_starts_with($name, 'targetStatuses.')) {
            $id = (int) substr($name, strlen('targetStatuses.'));
            $this->updateTargetStatus($id, $value);
        }
    }

    public function getUsersProperty()
    {
        return \App\Models\User::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $campaign = $this->getCampaignProperty();
        if (!$campaign) {
            return $this->redirect(route('crm.campaigns'), navigate: true);
        }
        $targets = $campaign->targets()->with(['cliente', 'lead', 'assignedTo'])->orderBy('estado')->paginate(20);
        foreach ($targets as $t) {
            if (!array_key_exists($t->id, $this->targetAssignments)) {
                $this->targetAssignments[$t->id] = $t->assigned_to ? (string) $t->assigned_to : '';
            }
            if (!array_key_exists($t->id, $this->targetStatuses)) {
                $this->targetStatuses[$t->id] = $t->estado ?? 'pending';
            }
        }
        return view('livewire.crm.campaign-detail-live', [
            'campaign' => $campaign,
            'targets' => $targets,
        ]);
    }
}
