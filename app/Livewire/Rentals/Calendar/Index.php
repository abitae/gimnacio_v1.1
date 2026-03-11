<?php

namespace App\Livewire\Rentals\Calendar;

use App\Models\Core\Rental;
use App\Models\Core\RentableSpace;
use Carbon\Carbon;
use Livewire\Component;

class Index extends Component
{
    public string $fecha = '';

    public ?int $spaceId = null;

    public function mount(): void
    {
        $this->authorize('rentals.view');
        $this->fecha = request()->query('fecha', now()->format('Y-m-d'));
    }

    public function render()
    {
        $fecha = Carbon::parse($this->fecha);
        $spaces = RentableSpace::activos()->orderBy('nombre')->get();
        $rentals = Rental::query()
            ->with(['rentableSpace', 'cliente'])
            ->whereDate('fecha', $this->fecha)
            ->when($this->spaceId, fn ($q) => $q->where('rentable_space_id', $this->spaceId))
            ->whereNotIn('estado', ['cancelado'])
            ->orderBy('hora_inicio')
            ->get();

        return view('livewire.rentals.calendar.index', [
            'fechaCarbon' => $fecha,
            'spaces' => $spaces,
            'rentals' => $rentals,
        ])->layout('layouts.app', ['title' => 'Calendario de alquileres']);
    }
}
