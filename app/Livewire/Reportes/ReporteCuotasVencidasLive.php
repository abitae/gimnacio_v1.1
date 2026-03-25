<?php

namespace App\Livewire\Reportes;

use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\ManagesCuotaPagoModal;
use App\Models\Core\EnrollmentInstallment;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCuotasVencidasLive extends Component
{
    use FlashesToast;
    use ManagesCuotaPagoModal;
    use WithPagination;

    public string $estadoFilter = '';

    public int $perPage = 20;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reportes.view');
    }

    protected function cuotaPagoClienteIdScope(): ?int
    {
        return null;
    }

    protected function afterCuotaPagoRegistrado(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = EnrollmentInstallment::query()
            ->with(['plan.cliente', 'clienteMatricula.membresia', 'clienteMatricula.clase'])
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->where('fecha_vencimiento', '<=', now()->toDateString())
            ->orderBy('fecha_vencimiento');

        if ($this->estadoFilter === 'vencida') {
            $query->where('estado', 'vencida');
        } elseif ($this->estadoFilter === 'pendiente') {
            $query->where('estado', 'pendiente');
        }

        $totalMonto = (clone $query)->get()->sum(fn ($c) => (float) $c->monto);
        $cuotas = $query->paginate($this->perPage);

        $paymentMethods = $this->cuotaPagoModalAbierto
            ? $this->paymentMethodsForCuotaModal()
            : collect();

        return view('livewire.reportes.reporte-cuotas-vencidas-live', [
            'cuotas' => $cuotas,
            'totalMonto' => $totalMonto,
            'paymentMethods' => $paymentMethods,
        ])->layout('layouts.app', ['title' => 'Cuotas vencidas']);
    }
}
