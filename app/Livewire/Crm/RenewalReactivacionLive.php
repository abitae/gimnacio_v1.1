<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Services\Crm\RenewalReactivationService;
use Livewire\Component;

class RenewalReactivacionLive extends Component
{
    use FlashesToast;

    public string $tab = 'renovacion';
    public string $renovacionDays = '7';
    public string $reactivacionDays = '30';
    public $modalCrearCampana = false;
    public $campanaNombre = '';
    public $campanaTipo = 'renovacion';
    public $campanaDias = '7';
    public $selectedIds = [];

    protected RenewalReactivationService $renewalService;

    public function boot(RenewalReactivationService $renewalService)
    {
        $this->renewalService = $renewalService;
    }

    public function mount()
    {
        $this->authorize('crm.view');
    }

    public function getRenovacionListProperty()
    {
        return $this->renewalService->getRenewals((int) $this->renovacionDays);
    }

    public function getReactivacionListProperty()
    {
        return $this->renewalService->getReactivation((int) $this->reactivacionDays);
    }

    public function openCrearCampana()
    {
        $this->authorize('crm.create');
        $this->campanaNombre = '';
        $this->campanaTipo = $this->tab === 'renovacion' ? 'renovacion' : 'reactivacion';
        $this->campanaDias = $this->tab === 'renovacion' ? $this->renovacionDays : $this->reactivacionDays;
        $this->selectedIds = [];
        if ($this->tab === 'renovacion') {
            $this->selectedIds = $this->renovacionList->pluck('cliente_id')->unique()->values()->map(fn ($id) => (string) $id)->all();
        } else {
            $this->selectedIds = $this->reactivacionList->pluck('cliente_id')->unique()->values()->map(fn ($id) => (string) $id)->all();
        }
        $this->modalCrearCampana = true;
    }

    public function crearCampana()
    {
        $this->authorize('crm.create');
        $this->validate(['campanaNombre' => 'required|string|max:120']);
        $campaignService = app(\App\Services\Crm\CampaignService::class);
        $campaign = $campaignService->create([
            'nombre' => $this->campanaNombre,
            'tipo' => $this->campanaTipo,
            'estado' => 'active',
            'filtros' => [
                'tipo' => $this->campanaTipo,
                'dias_renovacion' => $this->campanaTipo === 'renovacion' ? (int) $this->campanaDias : null,
                'vencidos_dias' => $this->campanaTipo === 'reactivacion' ? (int) $this->campanaDias : null,
                'cliente_ids' => array_map('intval', $this->selectedIds),
            ],
        ]);
        $count = $campaignService->generateTargets($campaign, $campaign->filtros);
        $this->modalCrearCampana = false;
        $this->flashToast('success', "Campaña creada con {$count} contactos.");
        return $this->redirect(route('crm.campaigns.show', $campaign->id), navigate: true);
    }

    public function closeCrearCampana()
    {
        $this->modalCrearCampana = false;
    }

    public function render()
    {
        return view('livewire.crm.renewal-reactivacion-live');
    }
}
