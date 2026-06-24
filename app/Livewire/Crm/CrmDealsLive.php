<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Deal;
use App\Services\Crm\DealService;
use Livewire\Component;
use Livewire\WithPagination;

class CrmDealsLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';

    public $estadoFilter = '';

    public $assignedFilter = 'me';

    public $perPage = 15;

    public $showMarkLost = false;

    public $markingDealId = null;

    public $motivo_perdida_id = '';

    public $observacion_perdida = '';

    protected DealService $dealService;

    protected $paginationTheme = 'tailwind';

    public function boot(DealService $dealService)
    {
        $this->dealService = $dealService;
    }

    public function mount()
    {
        $this->authorize('crm.ver');
    }

    public function markWon(int $dealId)
    {
        $this->authorize('crm.editar');
        $deal = Deal::find($dealId);
        if (! $deal) {
            return;
        }
        $this->dealService->markWon($deal);
        $this->flashToast('success', 'Oportunidad marcada como ganada');
    }

    public function openMarkLost(int $dealId)
    {
        $this->authorize('crm.editar');
        $this->markingDealId = $dealId;
        $this->motivo_perdida_id = '';
        $this->observacion_perdida = '';
        $this->showMarkLost = true;
    }

    public function markLost()
    {
        $this->authorize('crm.editar');
        $this->validate(['motivo_perdida_id' => 'required|exists:loss_reasons,id']);
        $deal = Deal::find($this->markingDealId);
        if (! $deal) {
            return;
        }
        $this->dealService->markLost($deal, (int) $this->motivo_perdida_id, $this->observacion_perdida ?: null);
        $this->showMarkLost = false;
        $this->markingDealId = null;
        $this->flashToast('success', 'Oportunidad marcada como perdida');
    }

    public function closeMarkLost()
    {
        $this->showMarkLost = false;
        $this->markingDealId = null;
    }

    public function getLossReasonsProperty()
    {
        return $this->dealService->getLossReasons();
    }

    public function render()
    {
        $filters = [
            'assigned_to' => $this->assignedFilter ?: null,
            'estado' => $this->estadoFilter ?: null,
            'search' => $this->search ?: null,
        ];
        $deals = $this->dealService->paginate($filters, $this->perPage);

        return view('livewire.crm.crm-deals-live', ['deals' => $deals]);
    }
}
