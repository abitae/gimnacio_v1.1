<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
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

    protected DealService $dealService;
    protected $paginationTheme = 'tailwind';

    public function boot(DealService $dealService)
    {
        $this->dealService = $dealService;
    }

    public function mount()
    {
        $this->authorize('crm.view');
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
