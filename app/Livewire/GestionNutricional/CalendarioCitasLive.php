<?php

namespace App\Livewire\GestionNutricional;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\User;
use App\Services\CitaService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CalendarioCitasLive extends Component
{
    use FlashesToast;

    public $modalCita = false;

    public $modalDetalle = false;

    public $citaId = null;

    public $estadoCita = '';

    public string $citaClienteSearch = '';

    public $clientesCita = [];

    public ?int $selectedClienteCitaId = null;

    public $selectedClienteCita = null;

    public bool $isSearchingClienteCita = false;

    public $citaFormData = [
        'tipo' => 'evaluacion',
        'fecha_hora' => '',
        'duracion_minutos' => 60,
        'nutricionista_id' => '',
        'trainer_user_id' => '',
        'estado' => 'programada',
        'observaciones' => '',
    ];

    protected CitaService $citaService;

    public function boot(CitaService $citaService)
    {
        $this->citaService = $citaService;
    }

    public function mount()
    {
        $this->authorize('gestion_nutricional.ver');
        $this->resetCitaForm();
    }

    public function abrirCrearCita()
    {
        $this->authorize('gestion_nutricional.crear');
        $this->resetCitaForm();
        $this->modalCita = true;
    }

    public function cerrarCrearCita()
    {
        $this->modalCita = false;
        $this->resetValidation();
    }

    public function updatedCitaClienteSearch()
    {
        if ($this->selectedClienteCita) {
            $nombreSeleccionado = trim($this->selectedClienteCita->nombres.' '.$this->selectedClienteCita->apellidos);
            if (trim($this->citaClienteSearch) !== $nombreSeleccionado) {
                $this->selectedClienteCitaId = null;
                $this->selectedClienteCita = null;
            }
        }

        $this->buscarClientesCita();
    }

    public function buscarClientesCita()
    {
        $term = trim($this->citaClienteSearch);
        $this->isSearchingClienteCita = true;

        if (strlen($term) < 2) {
            $this->clientesCita = [];
            $this->isSearchingClienteCita = false;

            return;
        }

        $this->clientesCita = Cliente::query()
            ->select(['id', 'codigo', 'numero_documento', 'nombres', 'apellidos', 'telefono', 'estado_cliente'])
            ->where(function ($query) use ($term) {
                $query->where('codigo', 'like', '%'.$term.'%')
                    ->orWhere('numero_documento', 'like', '%'.$term.'%')
                    ->orWhere('nombres', 'like', '%'.$term.'%')
                    ->orWhere('apellidos', 'like', '%'.$term.'%')
                    ->orWhereRaw("CONCAT(nombres, ' ', apellidos) LIKE ?", ['%'.$term.'%']);
            })
            ->orderBy('nombres')
            ->limit(8)
            ->get();

        $this->isSearchingClienteCita = false;
    }

    public function seleccionarClienteCita($clienteId)
    {
        $cliente = Cliente::find((int) $clienteId);
        if (! $cliente) {
            $this->flashToast('error', 'Cliente no encontrado');

            return;
        }

        $this->selectedClienteCitaId = $cliente->id;
        $this->selectedClienteCita = $cliente;
        $this->citaClienteSearch = trim($cliente->nombres.' '.$cliente->apellidos);
        $this->clientesCita = [];
        $this->resetValidation('selectedClienteCitaId');
    }

    public function limpiarClienteCita()
    {
        $this->selectedClienteCitaId = null;
        $this->selectedClienteCita = null;
        $this->citaClienteSearch = '';
        $this->clientesCita = [];
    }

    public function guardarCita()
    {
        $this->authorize('gestion_nutricional.crear');

        $this->validate([
            'selectedClienteCitaId' => ['required', 'exists:clientes,id'],
            'citaFormData.tipo' => ['required', 'in:evaluacion,consulta_nutricional,seguimiento,otro'],
            'citaFormData.fecha_hora' => ['required', 'date'],
            'citaFormData.duracion_minutos' => ['required', 'integer', 'min:15', 'max:480'],
            'citaFormData.nutricionista_id' => ['nullable', 'exists:users,id'],
            'citaFormData.trainer_user_id' => ['nullable', 'exists:users,id'],
            'citaFormData.estado' => ['required', 'in:programada,confirmada,en_curso,completada,cancelada,no_asistio'],
            'citaFormData.observaciones' => ['nullable', 'string'],
        ], [
            'selectedClienteCitaId.required' => 'Selecciona un cliente.',
            'citaFormData.fecha_hora.required' => 'Indica la fecha y hora.',
            'citaFormData.duracion_minutos.required' => 'Indica la duracion.',
        ]);

        try {
            $this->citaService->create([
                'cliente_id' => $this->selectedClienteCitaId,
                'tipo' => $this->citaFormData['tipo'],
                'fecha_hora' => $this->citaFormData['fecha_hora'],
                'duracion_minutos' => (int) ($this->citaFormData['duracion_minutos'] ?? 60),
                'nutricionista_id' => $this->citaFormData['nutricionista_id'] ?: null,
                'trainer_user_id' => $this->citaFormData['trainer_user_id'] ?: null,
                'estado' => $this->citaFormData['estado'],
                'observaciones' => $this->citaFormData['observaciones'] ?: null,
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]);

            $this->flashToast('success', 'Cita creada.');
            $this->modalCita = false;
            $this->resetCitaForm();
            $this->dispatch('calendario-refrescar');
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
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
        $this->authorize('gestion_nutricional.editar');
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

        $nutricionistas = User::role('nutricionista')->orderBy('name')->get();
        if ($nutricionistas->isEmpty()) {
            $nutricionistas = User::orderBy('name')->limit(20)->get();
        }

        $trainers = User::role('trainer')->orderBy('name')->get();

        return view('livewire.gestion-nutricional.calendario-citas-live', [
            'cita' => $cita,
            'nutricionistas' => $nutricionistas,
            'trainers' => $trainers,
        ]);
    }

    protected function resetCitaForm(): void
    {
        $this->citaFormData = [
            'tipo' => 'evaluacion',
            'fecha_hora' => now()->addDay()->format('Y-m-d\TH:i'),
            'duracion_minutos' => 60,
            'nutricionista_id' => '',
            'trainer_user_id' => '',
            'estado' => 'programada',
            'observaciones' => '',
        ];
        $this->limpiarClienteCita();
    }
}
