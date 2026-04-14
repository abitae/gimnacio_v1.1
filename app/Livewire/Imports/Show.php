<?php

namespace App\Livewire\Imports;

use App\Models\Import;
use App\Support\Imports\ImportType;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Import $import;

    public function mount(Import $import): void
    {
        $this->authorize('importacion.ver');
        $this->import = $import->load(['sucursal', 'importedBy']);
    }

    public function render()
    {
        $rows = $this->import->rows()->orderBy('fila_numero')->paginate(50);

        return view('livewire.imports.show', [
            'rows' => $rows,
            'tipoLabel' => ImportType::labels()[$this->import->tipo_importacion] ?? $this->import->tipo_importacion,
        ])->layout('layouts.app', ['title' => 'Importación #'.$this->import->id]);
    }
}
