<?php

namespace App\Livewire\Reportes;

use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteFinancieroLive extends Component
{
    public $fechaDesde = '';

    public $fechaHasta = '';

    public bool $mostrarModalTicketPago = false;

    public ?int $pagoIdTicket = null;

    public function mount(): void
    {
        $this->authorize('reportes.view');
        $this->fechaDesde = now()->startOfMonth()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function abrirTicketPago(int $pagoId): void
    {
        $this->pagoIdTicket = $pagoId;
        $this->mostrarModalTicketPago = true;
    }

    public function cerrarModalTicketPago(): void
    {
        $this->mostrarModalTicketPago = false;
        $this->pagoIdTicket = null;
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteFinanciero($this->fechaDesde, $this->fechaHasta);

        return view('livewire.reportes.reporte-financiero-live', [
            'pagos' => $data['pagos'],
            'ventas' => $data['ventas'],
            'resumen' => $data['resumen'],
        ]);
    }
}
