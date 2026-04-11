<?php

namespace App\Livewire\Rentals;

use App\Models\Core\Rental;
use Livewire\Component;
use Livewire\WithPagination;

class Report extends Component
{
    use WithPagination;

    public string $fechaDesde = '';

    public string $fechaHasta = '';

    public int $perPage = 20;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('alquiler.ver');
        $this->fechaDesde = request()->query('desde', now()->startOfMonth()->format('Y-m-d'));
        $this->fechaHasta = request()->query('hasta', now()->format('Y-m-d'));
    }

    public function render()
    {
        $query = Rental::query()
            ->with(['rentableSpace', 'cliente'])
            ->whereIn('estado', ['pagado', 'finalizado'])
            ->whereBetween('fecha', [$this->fechaDesde, $this->fechaHasta])
            ->orderByDesc('fecha')->orderByDesc('hora_inicio');

        $rentals = $query->paginate($this->perPage);
        $totalIngresos = (float) (clone $query)
            ->reorder()
            ->selectRaw('COALESCE(SUM(precio - descuento), 0) as total_ingresos')
            ->value('total_ingresos');

        return view('livewire.rentals.report', [
            'rentals' => $rentals,
            'totalIngresos' => $totalIngresos,
        ])->layout('layouts.app', ['title' => 'Ingresos por alquileres']);
    }
}
