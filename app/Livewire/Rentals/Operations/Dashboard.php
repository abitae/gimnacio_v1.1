<?php

namespace App\Livewire\Rentals\Operations;

use App\Models\Core\Rental;
use App\Services\RentalService;
use App\Services\SucursalContext;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(RentalService $rentalService, SucursalContext $sucursalContext)
    {
        $hoy = now();
        $sucursalId = $sucursalContext->getSucursalId();
        $hoyReservas = $rentalService->listForDate($hoy, $sucursalId);
        $proximas = Rental::query()
            ->with(['cliente', 'rentableSpace'])
            ->whereDate('fecha', '>', $hoy->toDateString())
            ->whereDate('fecha', '<=', $hoy->copy()->addDays(2)->toDateString())
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->whereNotIn('estado', ['cancelado'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit(30)
            ->get();

        $pendientesConfirmacion = $hoyReservas->where('estado', 'reservado')->count();

        return view('livewire.rentals.operations.dashboard', [
            'hoyReservas' => $hoyReservas,
            'proximas' => $proximas,
            'pendientesConfirmacion' => $pendientesConfirmacion,
        ])->layout('layouts.app', ['title' => __('Bandeja de alquileres')]);
    }
}
