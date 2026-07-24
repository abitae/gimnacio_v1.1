<?php

namespace App\Livewire\Crm;

use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\Crm\CrmReportService;
use Livewire\Component;

class CrmReportesLive extends Component
{
    use ScopesReporteBySucursal;

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
        $this->authorize('crm.ver');
        $this->mountReporteSucursalScope();
        $this->from = now()->startOfMonth()->format('Y-m-d');
        $this->to = now()->format('Y-m-d');
    }

    public function getConversionDataProperty(): array
    {
        return $this->reportService->reportConversion(
            $this->from ?: null,
            $this->to ?: null,
            $this->reporteSucursalFilter(),
        );
    }

    public function getByAdvisorDataProperty()
    {
        return $this->reportService->reportByAdvisor(
            $this->from ?: null,
            $this->to ?: null,
            $this->reporteSucursalFilter(),
        );
    }

    public function getByChannelDataProperty()
    {
        return $this->reportService->reportByChannel(
            $this->from ?: null,
            $this->to ?: null,
            $this->reporteSucursalFilter(),
        );
    }

    public function render()
    {
        return view('livewire.crm.crm-reportes-live', $this->reporteSucursalScopeViewData());
    }
}
