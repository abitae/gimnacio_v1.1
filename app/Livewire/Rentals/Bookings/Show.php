<?php

namespace App\Livewire\Rentals\Bookings;

use App\Models\Core\Rental;
use Livewire\Component;

class Show extends Component
{
    public Rental $rental;

    public function mount(Rental $rental): void
    {
        $this->authorize('rentals.view');
        $this->rental = $rental->load(['rentableSpace', 'cliente', 'payments.paymentMethod']);
    }

    public function render()
    {
        return view('livewire.rentals.bookings.show')
            ->layout('layouts.app', ['title' => 'Reserva']);
    }
}
