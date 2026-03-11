<?php

namespace App\Livewire\Rentals;

use App\Models\Core\Rental;
use Carbon\Carbon;
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
        $this->authorize('rentals.view');
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
        $totalIngresos = (clone $query)->get()->sum(fn ($r) => (float) $r->precio - (float) $r->descuento);

        return view('livewire.rentals.report', [
            'rentals' => $rentals,
            'totalIngresos' => $totalIngresos,
        ])->layout('layouts.app', ['title' => 'Ingresos por alquileres']);
    }
}
