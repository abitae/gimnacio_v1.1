<?php

namespace App\Livewire\Reportes;

use Livewire\Component;

class ReporteIndexLive extends Component
{
    public function mount(): void
    {
        $this->authorize('reportes.view');
    }

    public function render()
    {
        return view('livewire.reportes.reporte-index-live');
    }
}
