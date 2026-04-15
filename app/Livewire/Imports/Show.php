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

    public string $phaseFilter = 'all';

    public string $stateFilter = 'all';

    public function mount(Import $import): void
    {
        $this->authorize('importacion.ver');
        $this->import = $import->load(['sucursal', 'importedBy']);
    }

    public function render()
    {
        $query = $this->import->rows()->orderBy('fila_numero');

        if ($this->stateFilter !== 'all') {
            $query->where('estado', $this->stateFilter);
        }

        if ($this->phaseFilter !== 'all') {
            $query->where('data_json->phase', $this->phaseFilter);
        }

        return view('livewire.imports.show', [
            'rows' => $query->paginate(50),
            'tipoLabel' => ImportType::labels()[$this->import->tipo_importacion] ?? $this->import->tipo_importacion,
            'phaseSummaries' => $this->import->opciones['phase_summaries'] ?? [],
        ])->layout('layouts.app', ['title' => 'Importacion #'.$this->import->id]);
    }
}
