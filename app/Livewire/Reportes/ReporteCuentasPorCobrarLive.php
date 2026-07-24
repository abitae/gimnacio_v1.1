<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Services\Analytics\FinanceAnalyticsService;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCuentasPorCobrarLive extends Component
{
    use ScopesReporteBySucursal;
    use WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public string $fechaInicio = '';

    public string $fechaFin = '';

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    protected FinanceAnalyticsService $financeAnalyticsService;

    public function boot(FinanceAnalyticsService $financeAnalyticsService): void
    {
        $this->financeAnalyticsService = $financeAnalyticsService;
    }

    public function mount(): void
    {
        $this->authorize('reporte.ver');
        $this->mountReporteSucursalScope();
        $this->fechaInicio = now()->startOfMonth()->format('Y-m-d');
        $this->fechaFin = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function updatingFechaInicio(): void
    {
        $this->resetPage();
    }

    public function updatingFechaFin(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{search?: string, estado?: string, fecha_inicio?: string, fecha_fin?: string}
     */
    protected function filters(): array
    {
        return array_filter([
            'search' => trim($this->search) ?: null,
            'estado' => $this->estadoFilter ?: null,
            'fecha_inicio' => $this->fechaInicio ?: null,
            'fecha_fin' => $this->fechaFin ?: null,
        ]);
    }

    public function render()
    {
        $filters = $this->filters();
        $scopeFilter = $this->reporteSucursalFilter();

        return view('livewire.reportes.reporte-cuentas-por-cobrar-live', array_merge([
            'debts' => $this->financeAnalyticsService->paginateAccountsReceivable($filters, $this->perPage, $scopeFilter),
            'summary' => $this->financeAnalyticsService->accountsReceivableSummary($filters, $scopeFilter),
            'puedeCobrarOperativo' => auth()->user()?->can('punto_venta.ver') ?? false,
        ], $this->reporteSucursalScopeViewData()))->layout('layouts.app', ['title' => 'Reporte — Cuentas por cobrar']);
    }

    protected function resetReportePagination(): void
    {
        $this->resetPage();
    }
}
