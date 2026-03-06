<?php

namespace App\Livewire\Clients\Routines;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\RoutineTemplate;
use App\Services\ClientRoutineService;
use Livewire\Component;

class Assign extends Component
{
    use FlashesToast;

    public string $tipo_documento = 'DNI';
    public string $numero_documento = '';
    public ?Cliente $cliente = null;
    public ?int $routine_template_id = null;
    public string $fecha_inicio = '';
    public string $objetivo_personal = '';
    public string $restricciones = '';

    public function mount(): void
    {
        $this->authorize('ejercicios-rutinas.create');
        $this->fecha_inicio = now()->toDateString();
    }

    public function buscarCliente(): void
    {
        $this->validate([
            'tipo_documento' => ['required', 'string', 'in:DNI,CE'],
            'numero_documento' => ['required', 'string', 'max:20'],
        ], [
            'numero_documento.required' => 'Ingresa el número de documento.',
        ]);
        $this->cliente = Cliente::where('tipo_documento', $this->tipo_documento)
            ->where('numero_documento', $this->numero_documento)
            ->first();
        if (! $this->cliente) {
            $this->flashToast('error', 'No se encontró un cliente con ese documento.');
        }
    }

    public function asignar(ClientRoutineService $service): void
    {
        $this->authorize('ejercicios-rutinas.create');
        $this->validate([
            'cliente' => ['required'],
            'routine_template_id' => ['required', 'exists:routine_templates,id'],
            'fecha_inicio' => ['required', 'date'],
            'objetivo_personal' => ['nullable', 'string'],
            'restricciones' => ['nullable', 'string'],
        ], [
            'cliente.required' => 'Busca y selecciona un cliente primero.',
            'routine_template_id.required' => 'Selecciona una rutina base.',
        ]);
        $template = RoutineTemplate::find($this->routine_template_id);
        if (! $template) {
            $this->flashToast('error', 'Rutina no encontrada.');
            return;
        }
        $routine = $service->assignFromTemplate($this->cliente, $template, auth()->user(), [
            'fecha_inicio' => $this->fecha_inicio,
            'objetivo_personal' => $this->objetivo_personal ?: null,
            'restricciones' => $this->restricciones ?: null,
        ]);
        $this->flashToast('success', 'Rutina asignada correctamente.');
        $this->redirect(route('clientes.rutinas.index', $this->cliente), navigate: true);
    }

    public function render()
    {
        $templates = RoutineTemplate::where('estado', 'activa')->orderBy('nombre')->get();
        return view('livewire.clients.routines.assign', ['templates' => $templates]);
    }
}
