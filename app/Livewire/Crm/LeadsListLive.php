<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Services\Crm\LeadService;
use Livewire\Component;
use Livewire\WithPagination;

class LeadsListLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';
    public $stage_id = '';
    public $assignedFilter = '';
    public $canalFilter = '';
    public $perPage = 20;

    protected LeadService $leadService;
    protected $paginationTheme = 'tailwind';

    public function boot(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function mount()
    {
        $this->authorize('crm.view');
        $this->stage_id = request()->query('stage_id', '');
    }

    public function getCanalesProperty()
    {
        return $this->leadService->getDistinctCanales();
    }

    public function getStagesProperty()
    {
        return \App\Models\Crm\CrmStage::orderBy('orden')->get(['id', 'nombre']);
    }

    public function getUsersProperty()
    {
        return \App\Models\User::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'stage_id' => $this->stage_id ?: null,
            'assigned_to' => $this->assignedFilter ?: null,
            'canal_origen' => $this->canalFilter ?: null,
        ];
        $leads = $this->leadService->paginate($filters, $this->perPage);
        return view('livewire.crm.leads-list-live', ['leads' => $leads]);
    }
}
