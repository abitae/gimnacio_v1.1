<?php

namespace App\Livewire\Imports;

use App\Models\Import;
use App\Models\System\Sucursal;
use App\Support\Imports\ImportType;
use Livewire\Component;
use Livewire\WithPagination;

class History extends Component
{
    use WithPagination;

    public string $sucursalFiltro = '';

    public function mount(): void
    {
        $this->authorize('importacion.ver');
    }

    public function updatingSucursalFiltro(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Import::query()
            ->with(['sucursal', 'importedBy'])
            ->orderByDesc('created_at');

        if ($this->sucursalFiltro !== '' && ctype_digit($this->sucursalFiltro)) {
            $query->where('sucursal_id', (int) $this->sucursalFiltro);
        }

        $imports = $query->paginate(20);
        $sucursales = Sucursal::query()->where('estado', 'activa')->orderBy('nombre')->get();

        return view('livewire.imports.history', [
            'imports' => $imports,
            'sucursales' => $sucursales,
            'tipoLabels' => ImportType::labels(),
        ])->layout('layouts.app', ['title' => 'Historial de importaciones']);
    }
}
