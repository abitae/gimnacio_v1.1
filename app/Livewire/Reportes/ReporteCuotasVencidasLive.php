<?php

namespace App\Livewire\Reportes;

use App\Models\Core\EnrollmentInstallment;
use Livewire\Component;
use Livewire\WithPagination;

class ReporteCuotasVencidasLive extends Component
{
    use WithPagination;

    public string $estadoFilter = '';

    public int $perPage = 20;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('reportes.view');
    }

    public function render()
    {
        $query = EnrollmentInstallment::query()
            ->with(['plan.clienteMatricula.cliente', 'plan.clienteMatricula.membresia', 'plan.clienteMatricula.clase'])
            ->whereIn('estado', ['pendiente', 'vencida'])
            ->where('fecha_vencimiento', '<=', now()->toDateString())
            ->orderBy('fecha_vencimiento');

        if ($this->estadoFilter === 'vencida') {
            $query->where('estado', 'vencida');
        } elseif ($this->estadoFilter === 'pendiente') {
            $query->where('estado', 'pendiente');
        }

        $cuotas = $query->paginate($this->perPage);
        $totalMonto = (clone $query)->get()->sum(fn ($c) => (float) $c->monto);

        return view('livewire.reportes.reporte-cuotas-vencidas-live', [
            'cuotas' => $cuotas,
            'totalMonto' => $totalMonto,
        ])->layout('layouts.app', ['title' => 'Cuotas vencidas']);
    }
}
