<?php

namespace App\Livewire\Crm;

use App\Services\Crm\CrmReportService;
use Livewire\Component;

class CrmReportesLive extends Component
{
    public string $tab = 'conversion';
    public $from = '';
    public $to = '';

    protected CrmReportService $reportService;

    public function boot(CrmReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function mount()
    {
        $this->authorize('crm.view');
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
    }

    public function getConversionDataProperty(): array
    {
        return $this->reportService->reportConversion(
            $this->from ?: null,
            $this->to ?: null
        );
    }

    public function getByAdvisorDataProperty()
    {
        return $this->reportService->reportByAdvisor(
            $this->from ?: null,
            $this->to ?: null
        );
    }

    public function getByChannelDataProperty()
    {
        return $this->reportService->reportByChannel(
            $this->from ?: null,
            $this->to ?: null
        );
    }

    public function render()
    {
        return view('livewire.crm.crm-reportes-live');
    }
}
