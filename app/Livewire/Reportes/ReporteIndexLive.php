<?php

namespace App\Livewire\Reportes;

use App\Services\SucursalContext;
use App\Support\ReporteCatalog;
use Livewire\Component;

class ReporteIndexLive extends Component
{
    public function mount(): void
    {
        ReporteCatalog::authorizeAny();
    }

    public function render()
    {
        return view('livewire.reportes.reporte-index-live', [
            'reportes' => ReporteCatalog::visibleFor(auth()->user()),
            'activeSucursalNombre' => app(SucursalContext::class)->getSucursalNombre(),
        ]);
    }
}
