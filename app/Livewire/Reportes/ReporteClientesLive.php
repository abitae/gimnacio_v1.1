<?php

namespace App\Livewire\Reportes;

use App\Models\User;
use App\Services\ReporteModuloService;
use Livewire\Component;

class ReporteClientesLive extends Component
{
    public $estadoFilter = '';

    public $fechaDesde = '';

    public $fechaHasta = '';

    /** ID del usuario que registró al cliente (created_by) */
    public $createdById = '';

    /** ID del usuario entrenador asignado (trainer_user_id) */
    public $trainerUserId = '';

    public $vigenciaFilter = '';

    public $ventanaDias = 15;

    public function mount(): void
    {
        $this->authorize('reportes.view');
        $this->fechaDesde = now()->subYear()->format('Y-m-d');
        $this->fechaHasta = now()->format('Y-m-d');
    }

    public function render()
    {
        $service = app(ReporteModuloService::class);
        $data = $service->datosReporteClientes(
            $this->estadoFilter ?: null,
            $this->fechaDesde ?: null,
            $this->fechaHasta ?: null,
            $this->createdById !== '' ? (int) $this->createdById : null,
            $this->trainerUserId !== '' ? (int) $this->trainerUserId : null,
            $this->vigenciaFilter ?: null,
            (int) $this->ventanaDias
        );

        $usuarios = User::orderBy('name')->get(['id', 'name']);

        return view('livewire.reportes.reporte-clientes-live', [
            'clientes' => $data['clientes'],
            'resumen' => $data['resumen'],
            'usuarios' => $usuarios,
        ]);
    }
}
