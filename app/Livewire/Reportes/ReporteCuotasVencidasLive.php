<?php

namespace App\Livewire\Reportes;

use App\Livewire\Reportes\Concerns\AuthorizesReportAccess;
use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\ManagesCuotaPagoModal;
use App\Livewire\Reportes\Concerns\ScopesReporteBySucursal;
use App\Models\Core\EnrollmentInstallment;
use App\Support\SucursalScope;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCuotasVencidasLive extends Component
{
    use AuthorizesReportAccess;
    use FlashesToast;
    use ManagesCuotaPagoModal;
    use ScopesReporteBySucursal;
    use WithPagination;

    public string $estadoFilter = '';

    public int $perPage = 20;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorizeReport('cuotas-vencidas');
        $this->mountReporteSucursalScope();
    }

    protected function cuotaPagoClienteIdScope(): ?int
    {
        return null;
    }

    protected function afterCuotaPagoRegistrado(?\App\Models\Core\Pago $pago = null): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $filter = $this->reporteSucursalFilter();

        $query = EnrollmentInstallment::query()
            ->with(['plan.cliente', 'clienteMatricula.membresia', 'clienteMatricula.clase'])
            ->whereIn('estado', ['pendiente', 'vencida', 'parcial'])
            ->where('fecha_vencimiento', '<=', now()->toDateString())
            ->orderBy('fecha_vencimiento');

        if (! $filter->isActive()) {
            $query = app(SucursalScope::class)->applyReportScope(
                $query,
                $filter->specificSucursalId(),
                $filter->isConsolidated()
            );
        }

        if ($this->estadoFilter === 'vencida') {
            $query->where('estado', 'vencida');
        } elseif ($this->estadoFilter === 'pendiente') {
            $query->where('estado', 'pendiente');
        } elseif ($this->estadoFilter === 'parcial') {
            $query->where('estado', 'parcial');
        }

        $totalMonto = (float) (clone $query)->get()->sum(fn (EnrollmentInstallment $installment) => $installment->saldo_pendiente);
        $cuotas = $query->paginate($this->perPage);

        $paymentMethods = $this->cuotaPagoModalAbierto
            ? $this->paymentMethodsForCuotaModal()
            : collect();

        return view('livewire.reportes.reporte-cuotas-vencidas-live', array_merge([
            'cuotas' => $cuotas,
            'totalMonto' => $totalMonto,
            'paymentMethods' => $paymentMethods,
        ], $this->reporteSucursalScopeViewData()))->layout('layouts.app', ['title' => 'Cuotas vencidas']);
    }

    protected function resetReportePagination(): void
    {
        $this->resetPage();
    }
}
