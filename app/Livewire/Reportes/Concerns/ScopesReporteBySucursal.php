<?php

namespace App\Livewire\Reportes\Concerns;

use App\Data\Reporte\ReporteSucursalFilter;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;

trait ScopesReporteBySucursal
{
    public string $reporteModoSucursal = ReporteSucursalFilter::MODE_ACTIVE;

    public string $reporteSucursalId = '';

    protected function mountReporteSucursalScope(): void
    {
        $activeId = app(SucursalContext::class)->getSucursalId();

        if ($activeId !== null) {
            $this->reporteSucursalId = (string) $activeId;
        }
    }

    public function reporteSucursalFilter(): ReporteSucursalFilter
    {
        return ReporteSucursalFilter::fromLivewire($this->reporteModoSucursal, $this->reporteSucursalId);
    }

    /**
     * @return array<string, mixed>
     */
    protected function reporteSucursalScopeViewData(): array
    {
        $user = auth()->user();
        $context = app(SucursalContext::class);
        $canChoose = $user
            && method_exists($user, 'hasRole')
            && $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)
            && $context->availableForUser($user)->count() > 1;

        return [
            'reportePuedeElegirSucursal' => $canChoose,
            'reporteSucursalesDisponibles' => $canChoose ? $context->availableForUser($user) : collect(),
            'reporteSucursalEtiqueta' => $this->reporteSucursalFilter()->etiqueta($context),
        ];
    }

    public function updatedReporteModoSucursal(): void
    {
        if ($this->reporteModoSucursal === ReporteSucursalFilter::MODE_ACTIVE) {
            $activeId = app(SucursalContext::class)->getSucursalId();
            $this->reporteSucursalId = $activeId ? (string) $activeId : '';
        }

        if ($this->reporteModoSucursal === ReporteSucursalFilter::MODE_CONSOLIDATED) {
            $this->reporteSucursalId = '';
        }

        $this->resetReportePagination();
    }

    public function updatedReporteSucursalId(): void
    {
        $this->resetReportePagination();
    }

    protected function resetReportePagination(): void
    {
        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }
}
