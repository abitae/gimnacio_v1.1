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

    public bool $advisorShowAll = false;

    public bool $channelShowAll = false;

    protected const ROWS_LIMIT = 15;

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

    public function getByAdvisorDataAllProperty()
    {
        return $this->reportService->reportByAdvisor(
            $this->from ?: null,
            $this->to ?: null,
            $this->reporteSucursalFilter(),
        )->sortByDesc('leads_count')->values();
    }

    public function getByAdvisorDataProperty()
    {
        $all = $this->getByAdvisorDataAllProperty();

        return $this->advisorShowAll ? $all : $all->take(self::ROWS_LIMIT);
    }

    public function getByChannelDataAllProperty()
    {
        return $this->reportService->reportByChannel(
            $this->from ?: null,
            $this->to ?: null,
            $this->reporteSucursalFilter(),
        )->sortByDesc('total')->values();
    }

    public function getByChannelDataProperty()
    {
        $all = $this->getByChannelDataAllProperty();

        return $this->channelShowAll ? $all : $all->take(self::ROWS_LIMIT);
    }

    public function render()
    {
        return view('livewire.crm.crm-reportes-live', $this->reporteSucursalScopeViewData());
    }
}
