<?php

namespace App\Livewire\GestionNutricional;

use App\Livewire\Concerns\FlashesToast;
use App\Services\CitaService;
use Livewire\Component;

class CalendarioCitasLive extends Component
{
    use FlashesToast;
    public $modalDetalle = false;

    public $citaId = null;

    public $estadoCita = '';

    protected CitaService $citaService;

    public function boot(CitaService $citaService)
    {
        $this->citaService = $citaService;
    }

    public function mount()
    {
        $this->authorize('gestion-nutricional.view');
    }

    public function abrirDetalleCita($id)
    {
        $id = (int) $id;
        $cita = $this->citaService->find($id);
        if (! $cita) {
            $this->flashToast('error', 'Cita no encontrada');
            return;
        }
        $this->citaId = $id;
        $this->estadoCita = $cita->estado;
        $this->modalDetalle = true;
    }

    public function cerrarDetalleCita()
    {
        $this->modalDetalle = false;
        $this->citaId = null;
        $this->estadoCita = '';
    }

    public function actualizarEstadoCita()
    {
        $this->authorize('gestion-nutricional.update');
        try {
            if (! $this->citaId) {
                return;
            }
            $this->validate([
                'estadoCita' => 'required|in:programada,confirmada,en_curso,completada,cancelada,no_asistio',
            ]);
            $this->citaService->update($this->citaId, [
                'estado' => $this->estadoCita,
                'updated_by' => auth()->id(),
            ]);
            $this->flashToast('success', 'Estado de la cita actualizado.');
            $this->dispatch('calendario-refrescar');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $cita = null;
        if ($this->citaId) {
            $cita = $this->citaService->find($this->citaId);
        }

        return view('livewire.gestion-nutricional.calendario-citas-live', [
            'cita' => $cita,
        ]);
    }
}
