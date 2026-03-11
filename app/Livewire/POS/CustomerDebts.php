<?php

namespace App\Livewire\Pos;

use App\Models\Core\Cliente;
use App\Models\Core\ClientDebt;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerDebts extends Component
{
    use WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        if (! auth()->user()->can('pos.view') && ! auth()->user()->can('reportes.view')) {
            abort(403);
        }
    }

    public function render()
    {
        $query = ClientDebt::query()
            ->with(['cliente', 'venta'])
            ->pendientes()
            ->orderByDesc('fecha_registro');

        if ($this->search) {
            $query->whereHas('cliente', fn ($q) => $q->where('nombres', 'like', '%' . $this->search . '%')
                ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                ->orWhere('numero_documento', 'like', '%' . $this->search . '%'));
        }

        if ($this->estadoFilter) {
            $query->where('estado', $this->estadoFilter);
        }

        $debts = $query->paginate($this->perPage);

        return view('livewire.pos.customer-debts', [
            'debts' => $debts,
        ])->layout('layouts.app', ['title' => 'Estado de cuenta - Deudas']);
    }
}
