<?php

namespace App\Livewire\MedidasNutricion;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Services\ClienteService;
use App\Services\CitaService;
use App\Services\EvaluacionMedidasNutricionService;
use App\Services\ReporteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;
use Livewire\WithPagination;

class MedidasNutricionLive extends Component
{
    use FlashesToast, WithPagination;

    // Cliente search
    public $clienteSearch = '';
    public $clientes;
    public $selectedClienteId = null;
    public $selectedCliente = null;

    // Tab selection
    public $activeTab = 'medidas';

    // Filters
    public $estadoFilter = '';
    public $tipoFilter = '';
    public $fechaFilter = '';
    public $perPage = 15;

    // Modal state
    public $modalState = [
        'evaluacion' => false,
        'cita' => false,
        'delete_evaluacion' => false,
        'delete_cita' => false,
        'reporte_preview' => false,
    ];

    public $evaluacionIdReporte = null;

    // Selected items
    public $evaluacionId = null;
    public $citaId = null;

    // Form data - Evaluación
    public $evaluacionFormData = [
        'peso' => '',
        'estatura' => '',
        'imc' => '',
        'porcentaje_grasa' => '',
        'porcentaje_musculo' => '',
        'masa_muscular' => '',
        'masa_grasa' => '',
        'masa_osea' => '',
        'masa_residual' => '',
        'circunferencias' => [
            'estatura' => '',
            'cuello' => '',
            'brazo_normal' => '',
            'brazo_contraido' => '',
            'torax' => '',
            'cintura' => '',
            'cintura_baja' => '',
            'cadera' => '',
            'muslo' => '',
            'gluteos' => '',
            'pantorrilla' => '',
        ],
        'presion_arterial' => '',
        'frecuencia_cardiaca' => '',
        'objetivo' => 'DEPORTES Ó SALUD',
        'nutricionista_id' => '',
        'fecha_proxima_evaluacion' => '',
        'estado' => 'completada',
        'observaciones' => '',
    ];

    // Form data - Cita
    public $citaFormData = [
        'tipo' => 'evaluacion',
        'fecha_hora' => '',
        'duracion_minutos' => 60,
        'nutricionista_id' => '',
        'trainer_user_id' => '',
        'estado' => 'programada',
        'observaciones' => '',
    ];

    // Trainer assignment
    public $trainerAsignacionId = null;

    protected $paginationTheme = 'tailwind';

    protected EvaluacionMedidasNutricionService $evaluacionService;
    protected CitaService $citaService;
    protected ClienteService $clienteService;
    protected ReporteService $reporteService;

    public function boot(
        EvaluacionMedidasNutricionService $evaluacionService,
        CitaService $citaService,
        ClienteService $clienteService,
        ReporteService $reporteService
    ) {
        $this->evaluacionService = $evaluacionService;
        $this->citaService = $citaService;
        $this->clienteService = $clienteService;
        $this->reporteService = $reporteService;
    }

    public function mount()
    {
        $this->clientes = collect([]);
        $this->evaluacionFormData['evaluado_por'] = auth()->id();
        $this->citaFormData['created_by'] = auth()->id();
    }

    public $isSearching = false;

    public function updatingClienteSearch($value)
    {
        $this->isSearching = true;
        
        // Si hay un cliente seleccionado y el texto de búsqueda es diferente al nombre del cliente,
        // limpiar la selección para permitir buscar otro cliente
        if ($this->selectedCliente) {
            $nombreCompleto = $this->selectedCliente->nombres . ' ' . $this->selectedCliente->apellidos;
            $valorTrim = trim($value);
            if ($valorTrim !== $nombreCompleto && $valorTrim !== '') {
                $this->selectedClienteId = null;
                $this->selectedCliente = null;
            }
        }
    }

    public function updatedClienteSearch()
    {
        $this->searchClientes();
    }

    public function updatingEstadoFilter()
    {
        $this->resetPage();
    }

    public function updatingActiveTab()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function searchClientes()
    {
        $searchTerm = trim($this->clienteSearch);
        
        if (strlen($searchTerm) >= 2) {
            $this->clientes = $this->clienteService->quickSearch($searchTerm, 10);
        } else {
            $this->clientes = collect([]);
        }
        
        $this->isSearching = false;
    }

    public function selectCliente($clienteId)
    {
        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);
        $this->clienteSearch = $this->selectedCliente->nombres . ' ' . $this->selectedCliente->apellidos;
        $this->clientes = collect([]);
        $this->resetPage();
    }

    public function clearClienteSelection()
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->resetPage();
    }

    // Evaluación methods
    public function openCreateEvaluacionModal()
    {
        if (!$this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero');
            return;
        }

        $this->resetEvaluacionForm();
        $this->evaluacionFormData['evaluado_por'] = auth()->id();
        $this->modalState['evaluacion'] = true;
    }

    public function openEditEvaluacionModal($id)
    {
        $evaluacion = $this->evaluacionService->find($id);

        if (!$evaluacion) {
            $this->flashToast('error', 'Evaluación no encontrada');
            return;
        }

        $this->evaluacionId = $evaluacion->id;
        $this->mapEvaluacionToForm($evaluacion);
        $this->modalState['evaluacion'] = true;
    }

    public function openDeleteEvaluacionModal($id)
    {
        $this->evaluacionId = $id;
        $this->modalState['delete_evaluacion'] = true;
    }

    public function saveEvaluacion()
    {
        try {
            if (!$this->selectedClienteId) {
                $this->flashToast('error', 'Debes seleccionar un cliente primero');
                return;
            }

            $data = $this->mapEvaluacionFormToData();
            $data['cliente_id'] = $this->selectedClienteId;
            $data['evaluado_por'] = auth()->id();

            if ($this->evaluacionId) {
                $this->evaluacionService->update($this->evaluacionId, $data);
                $this->flashToast('success', 'Evaluación actualizada correctamente');
            } else {
                $this->evaluacionService->create($data);
                $this->flashToast('success', 'Evaluación creada correctamente');
            }

            $this->closeEvaluacionModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function deleteEvaluacion()
    {
        try {
            $this->evaluacionService->delete($this->evaluacionId);
            $this->flashToast('success', 'Evaluación eliminada correctamente');
            $this->closeEvaluacionModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeEvaluacionModal()
    {
        $this->modalState['evaluacion'] = false;
        $this->modalState['delete_evaluacion'] = false;
        $this->evaluacionId = null;
        $this->resetEvaluacionForm();
    }

    public function updatedEvaluacionFormDataPeso()
    {
        $this->calcularIMC();
    }

    public function updatedEvaluacionFormDataEstatura()
    {
        $this->calcularIMC();
    }

    protected function calcularIMC()
    {
        if ($this->evaluacionFormData['peso'] && $this->evaluacionFormData['estatura'] && $this->evaluacionFormData['estatura'] > 0) {
            $this->evaluacionFormData['imc'] = round(
                $this->evaluacionFormData['peso'] / ($this->evaluacionFormData['estatura'] * $this->evaluacionFormData['estatura']),
                2
            );
        }
    }

    // Cita methods
    public function openCreateCitaModal()
    {
        if (!$this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero');
            return;
        }

        $this->resetCitaForm();
        $this->citaFormData['created_by'] = auth()->id();
        $this->citaFormData['fecha_hora'] = now()->addDay()->format('Y-m-d\TH:i');
        $this->modalState['cita'] = true;
    }

    public function openEditCitaModal($id)
    {
        $cita = $this->citaService->find($id);

        if (!$cita) {
            $this->flashToast('error', 'Cita no encontrada');
            return;
        }

        $this->citaId = $cita->id;
        $this->mapCitaToForm($cita);
        $this->modalState['cita'] = true;
    }

    public function openDeleteCitaModal($id)
    {
        $this->citaId = $id;
        $this->modalState['delete_cita'] = true;
    }

    public function saveCita()
    {
        try {
            if (!$this->selectedClienteId) {
                $this->flashToast('error', 'Debes seleccionar un cliente primero');
                return;
            }

            $data = $this->mapCitaFormToData();
            $data['cliente_id'] = $this->selectedClienteId;
            $data['created_by'] = auth()->id();
            $data['updated_by'] = auth()->id();

            if ($this->citaId) {
                $this->citaService->update($this->citaId, $data);
                $this->flashToast('success', 'Cita actualizada correctamente');
            } else {
                $this->citaService->create($data);
                $this->flashToast('success', 'Cita creada correctamente');
            }

            $this->closeCitaModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function cancelarCita($id)
    {
        try {
            $this->citaService->cancelar($id);
            $this->flashToast('success', 'Cita cancelada correctamente');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function completarCita($id, $evaluacionId = null)
    {
        try {
            $this->citaService->completar($id, $evaluacionId);
            $this->flashToast('success', 'Cita completada correctamente');
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function deleteCita()
    {
        try {
            $this->citaService->delete($this->citaId);
            $this->flashToast('success', 'Cita eliminada correctamente');
            $this->closeCitaModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function closeCitaModal()
    {
        $this->modalState['cita'] = false;
        $this->modalState['delete_cita'] = false;
        $this->citaId = null;
        $this->resetCitaForm();
    }

    // Trainer (usuario con rol trainer) methods
    public function asignarTrainer($clienteId, $trainerUserId)
    {
        try {
            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                throw new \Exception('Cliente no encontrado.');
            }
            $cliente->trainer_user_id = $trainerUserId;
            $cliente->save();
            $this->flashToast('success', 'Trainer asignado correctamente');
            if ($this->selectedClienteId == $clienteId) {
                $this->selectedCliente = $this->clienteService->find($clienteId);
            }
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function removerTrainer($clienteId)
    {
        try {
            $cliente = Cliente::find($clienteId);
            if (!$cliente) {
                throw new \Exception('Cliente no encontrado.');
            }
            $cliente->trainer_user_id = null;
            $cliente->save();
            $this->flashToast('success', 'Trainer removido correctamente');
            if ($this->selectedClienteId == $clienteId) {
                $this->selectedCliente = $this->clienteService->find($clienteId);
            }
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    // Reporte: abrir modal de previsualización (imprimir o descargar desde el modal)
    public function abrirPreviewReporte($evaluacionId)
    {
        $this->evaluacionIdReporte = $evaluacionId;
        $this->modalState['reporte_preview'] = true;
    }

    public function cerrarPreviewReporte()
    {
        $this->modalState['reporte_preview'] = false;
        $this->evaluacionIdReporte = null;
    }

    // Helper methods
    protected function mapEvaluacionToForm(EvaluacionMedidasNutricion $evaluacion): void
    {
        $this->evaluacionFormData = [
            'peso' => $evaluacion->peso ?? '',
            'estatura' => $evaluacion->estatura ?? '',
            'imc' => $evaluacion->imc ?? '',
            'porcentaje_grasa' => $evaluacion->porcentaje_grasa ?? '',
            'porcentaje_musculo' => $evaluacion->porcentaje_musculo ?? '',
            'masa_muscular' => $evaluacion->masa_muscular ?? '',
            'masa_grasa' => $evaluacion->masa_grasa ?? '',
            'masa_osea' => $evaluacion->masa_osea ?? '',
            'masa_residual' => $evaluacion->masa_residual ?? '',
            'circunferencias' => $evaluacion->circunferencias ?? [
                'estatura' => '',
                'cuello' => '',
                'brazo_normal' => '',
                'brazo_contraido' => '',
                'torax' => '',
                'cintura' => '',
                'cintura_baja' => '',
                'cadera' => '',
                'muslo' => '',
                'gluteos' => '',
                'pantorrilla' => '',
            ],
            'presion_arterial' => $evaluacion->presion_arterial ?? '',
            'frecuencia_cardiaca' => $evaluacion->frecuencia_cardiaca ?? '',
            'objetivo' => $evaluacion->objetivo ?? 'DEPORTES Ó SALUD',
            'nutricionista_id' => $evaluacion->nutricionista_id ?? '',
            'fecha_proxima_evaluacion' => $evaluacion->fecha_proxima_evaluacion ? $evaluacion->fecha_proxima_evaluacion->format('Y-m-d') : '',
            'estado' => $evaluacion->estado ?? 'completada',
            'observaciones' => $evaluacion->observaciones ?? '',
        ];
    }

    protected function mapEvaluacionFormToData(): array
    {
        return [
            'peso' => $this->evaluacionFormData['peso'] ?: null,
            'estatura' => $this->evaluacionFormData['estatura'] ?: null,
            'imc' => $this->evaluacionFormData['imc'] ?: null,
            'porcentaje_grasa' => $this->evaluacionFormData['porcentaje_grasa'] ?: null,
            'porcentaje_musculo' => $this->evaluacionFormData['porcentaje_musculo'] ?: null,
            'masa_muscular' => $this->evaluacionFormData['masa_muscular'] ?: null,
            'masa_grasa' => $this->evaluacionFormData['masa_grasa'] ?: null,
            'masa_osea' => $this->evaluacionFormData['masa_osea'] ?: null,
            'masa_residual' => $this->evaluacionFormData['masa_residual'] ?: null,
            'circunferencias' => $this->evaluacionFormData['circunferencias'],
            'presion_arterial' => $this->evaluacionFormData['presion_arterial'] ?: null,
            'frecuencia_cardiaca' => $this->evaluacionFormData['frecuencia_cardiaca'] ?: null,
            'objetivo' => $this->evaluacionFormData['objetivo'] ?: null,
            'nutricionista_id' => $this->evaluacionFormData['nutricionista_id'] ?: null,
            'fecha_proxima_evaluacion' => $this->evaluacionFormData['fecha_proxima_evaluacion'] ?: null,
            'estado' => $this->evaluacionFormData['estado'] ?? 'completada',
            'observaciones' => $this->evaluacionFormData['observaciones'] ?: null,
        ];
    }

    protected function mapCitaToForm(\App\Models\Core\Cita $cita): void
    {
        $this->citaFormData = [
            'tipo' => $cita->tipo,
            'fecha_hora' => $cita->fecha_hora->format('Y-m-d\TH:i'),
            'duracion_minutos' => $cita->duracion_minutos ?? 60,
            'nutricionista_id' => $cita->nutricionista_id ?? '',
            'trainer_user_id' => $cita->trainer_user_id ?? '',
            'estado' => $cita->estado,
            'observaciones' => $cita->observaciones ?? '',
        ];
    }

    protected function mapCitaFormToData(): array
    {
        return [
            'tipo' => $this->citaFormData['tipo'],
            'fecha_hora' => $this->citaFormData['fecha_hora'],
            'duracion_minutos' => $this->citaFormData['duracion_minutos'] ?? 60,
            'nutricionista_id' => $this->citaFormData['nutricionista_id'] ?: null,
            'trainer_user_id' => $this->citaFormData['trainer_user_id'] ?: null,
            'estado' => $this->citaFormData['estado'] ?? 'programada',
            'observaciones' => $this->citaFormData['observaciones'] ?: null,
        ];
    }

    protected function resetEvaluacionForm(): void
    {
        $this->evaluacionId = null;
        $this->evaluacionFormData = [
            'peso' => '',
            'estatura' => '',
            'imc' => '',
            'porcentaje_grasa' => '',
            'porcentaje_musculo' => '',
            'masa_muscular' => '',
            'masa_grasa' => '',
            'masa_osea' => '',
            'masa_residual' => '',
            'circunferencias' => [
                'estatura' => '',
                'cuello' => '',
                'brazo_normal' => '',
                'brazo_contraido' => '',
                'torax' => '',
                'cintura' => '',
                'cintura_baja' => '',
                'cadera' => '',
                'muslo' => '',
                'gluteos' => '',
                'pantorrilla' => '',
            ],
            'presion_arterial' => '',
            'frecuencia_cardiaca' => '',
            'objetivo' => 'DEPORTES Ó SALUD',
            'nutricionista_id' => '',
            'fecha_proxima_evaluacion' => '',
            'estado' => 'completada',
            'observaciones' => '',
        ];
    }

    protected function resetCitaForm(): void
    {
        $this->citaId = null;
        $this->citaFormData = [
            'tipo' => 'evaluacion',
            'fecha_hora' => '',
            'duracion_minutos' => 60,
            'nutricionista_id' => '',
            'trainer_user_id' => '',
            'estado' => 'programada',
            'observaciones' => '',
        ];
    }

    protected function handleValidationErrors(\Illuminate\Validation\ValidationException $e): void
    {
        foreach ($e->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->flashToast('error', $message);
            }
        }
    }

    public function render()
    {
        $evaluaciones = collect([]);
        $citas = collect([]);
        $ultimaEvaluacion = null;

        if ($this->selectedClienteId) {
            $filtrosEvaluacion = [];
            if ($this->estadoFilter) {
                $filtrosEvaluacion['estado'] = $this->estadoFilter;
            }

            $evaluaciones = $this->evaluacionService->getByCliente(
                $this->selectedClienteId,
                $filtrosEvaluacion,
                $this->perPage
            );

            $ultimaEvaluacion = $this->evaluacionService->getUltimaEvaluacion($this->selectedClienteId);

            $filtrosCita = [];
            if ($this->estadoFilter) {
                $filtrosCita['estado'] = $this->estadoFilter;
            }
            if ($this->tipoFilter) {
                $filtrosCita['tipo'] = $this->tipoFilter;
            }

            $citas = $this->citaService->getByCliente(
                $this->selectedClienteId,
                $filtrosCita,
                $this->perPage
            );
        }

        // Obtener listas para dropdowns
        $nutricionistas = \App\Models\User::whereHas('evaluacionesMedidasNutricion', function($q) {
            $q->whereNotNull('nutricionista_id');
        })->orWhere('email', 'like', '%nutricionista%')
        ->orWhere('name', 'like', '%nutricionista%')
        ->orWhere('name', 'like', '%jasmin%')
        ->orderBy('name')
        ->get();
        $trainers = \App\Models\User::role('trainer')->orderBy('name')->get();

        return view('livewire.medidas-nutricion.medidas-nutricion-live', [
            'evaluaciones' => $evaluaciones,
            'citas' => $citas,
            'ultimaEvaluacion' => $ultimaEvaluacion,
            'nutricionistas' => $nutricionistas,
            'trainers' => $trainers,
        ]);
    }
}
