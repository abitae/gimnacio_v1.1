<?php

namespace App\Livewire\Pos;

use App\Models\Core\Venta;
use Livewire\Component;
use Livewire\WithPagination;

class CreditSales extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('pos.view');
    }

    public function render()
    {
        $query = Venta::query()
            ->where('es_credito', true)
            ->with(['cliente', 'usuario'])
            ->orderByDesc('fecha_venta');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('numero_venta', 'like', '%' . $this->search . '%')
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombres', 'like', '%' . $this->search . '%')
                        ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                        ->orWhere('numero_documento', 'like', '%' . $this->search . '%'));
            });
        }

        $ventas = $query->paginate($this->perPage);

        return view('livewire.pos.credit-sales', [
            'ventas' => $ventas,
        ])->layout('layouts.app', ['title' => 'Ventas a crédito']);
    }
}
