<?php

namespace App\Livewire\ClienteMatriculas;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Services\ClienteMatriculaService;
use App\Services\ClienteService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteMatriculaLive extends Component
{
    use FlashesToast, WithPagination;

    // Cliente search
    public $clienteSearch = '';
    public $clientes;
    public $selectedClienteId = null;
    public $selectedCliente = null;

    // Tab selection (membresias or clases)
    public $activeTab = 'membresias';

    // Filters
    public $estadoFilter = '';
    public $tipoFilter = '';
    public $perPage = 15;

    // Modal state
    public $modalState = [
        'create' => false,
        'delete' => false,
    ];

    // Selected items
    public $clienteMatriculaId = null;

    /** ID de la matrícula que se está renovando (para aplicar estado congelada/activa según vigencia actual) */
    public $renovandoMatriculaId = null;

    // Form data
    public $formData = [
        'tipo' => 'membresia',
        'membresia_id' => '',
        'clase_id' => '',
        'fecha_matricula' => '',
        'fecha_inicio' => '',
        'fecha_fin' => '',
        'estado' => 'activa',
        'precio_lista' => 0.00,
        'descuento_monto' => 0.00,
        'precio_final' => 0.00,
        'modalidad_pago' => 'contado',
        'cuota_inicial_monto' => 0.00,
        'numero_cuotas' => null,
        'frecuencia_cuotas' => 'mensual',
        'fecha_inicio_plan_cuotas' => '',
        'asesor_id' => null,
        'canal_venta' => 'presencial',
        'fechas_congelacion' => [],
        'motivo_cancelacion' => '',
        'sesiones_totales' => null,
        'sesiones_usadas' => 0,
    ];

    public bool $membresiaPermiteCuotas = false;

    protected $paginationTheme = 'tailwind';

    protected ClienteMatriculaService $service;
    protected ClienteService $clienteService;

    public function boot(ClienteMatriculaService $service, ClienteService $clienteService)
    {
        $this->service = $service;
        $this->clienteService = $clienteService;
    }

    public function mount()
    {
        $this->authorize('cliente-matriculas.view');
        $this->formData['asesor_id'] = auth()->id();
        $this->formData['fecha_matricula'] = now()->format('Y-m-d');
        $this->clientes = collect([]);
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

    public function updatingTipoFilter()
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

    public function openCreateModal()
    {
        $this->authorize('cliente-matriculas.create');
        if (!$this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero');
            return;
        }

        $this->resetForm();
        $this->formData['tipo'] = $this->activeTab === 'membresias' ? 'membresia' : 'clase';
        if ($this->formData['tipo'] === 'membresia') {
            $this->formData['fecha_matricula'] = now()->format('Y-m-d');
            $this->formData['fecha_inicio'] = now()->format('Y-m-d');
            $this->formData['fecha_inicio_plan_cuotas'] = now()->format('Y-m-d');
            $this->updatedFormDataFechaInicio();
        } else {
            $this->formData['fecha_matricula'] = now()->format('Y-m-d');
            $this->formData['fecha_fin'] = '';
        }
        $this->modalState['create'] = true;
    }

    public function openEditModal($id)
    {
        $this->authorize('cliente-matriculas.update');
        $clienteMatricula = $this->service->find($id);

        if (!$clienteMatricula) {
            $this->flashToast('error', 'Matrícula no encontrada');
            return;
        }

        $this->clienteMatriculaId = $clienteMatricula->id;
        $this->mapClienteMatriculaToForm($clienteMatricula);
        $this->modalState['create'] = true;
    }

    public function openDeleteModal($id)
    {
        $this->authorize('cliente-matriculas.delete');
        $this->clienteMatriculaId = $id;
        $this->modalState['delete'] = true;
    }

    /**
     * Abre el modal de nueva matrícula para renovar una membresía próxima a vencer.
     * Selecciona el cliente, prellena membresía y fecha inicio (día siguiente al vencimiento).
     */
    public function openRenovarMembresia(int $clienteId, int $matriculaId): void
    {
        $this->authorize('cliente-matriculas.create');
        $matricula = $this->service->find($matriculaId);
        if (!$matricula || $matricula->tipo !== 'membresia' || (int) $matricula->cliente_id !== $clienteId) {
            $this->flashToast('error', 'Matrícula no encontrada o no es una membresía');
            return;
        }

        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);
        $this->clienteSearch = $this->selectedCliente ? ($this->selectedCliente->nombres . ' ' . $this->selectedCliente->apellidos) : '';
        $this->clientes = collect([]);
        $this->activeTab = 'membresias';

        $this->resetForm();
        $this->renovandoMatriculaId = $matriculaId;
        $this->formData['tipo'] = 'membresia';
        $this->formData['membresia_id'] = (string) $matricula->membresia_id;
        $this->formData['fecha_matricula'] = now()->format('Y-m-d');
        $this->formData['fecha_inicio'] = \Carbon\Carbon::parse($matricula->fecha_fin)->addDay()->format('Y-m-d');
        $this->updatedFormDataMembresiaId();
        // Congelada si la membresía actual sigue vigente (fecha_fin >= hoy); activa si ya venció
        $this->formData['estado'] = $matricula->fecha_fin && \Carbon\Carbon::parse($matricula->fecha_fin)->gte(\Carbon\Carbon::today())
            ? 'congelada'
            : 'activa';

        $this->modalState['create'] = true;
    }

    public function closeModal()
    {
        $this->modalState = [
            'create' => false,
            'delete' => false,
        ];
        $this->resetForm();
    }

    public function updatedFormDataTipo()
    {
        $this->formData['membresia_id'] = '';
        $this->formData['clase_id'] = '';
        $this->formData['precio_lista'] = 0.00;
        $this->formData['precio_final'] = 0.00;
        $this->formData['sesiones_totales'] = null;
        $this->membresiaPermiteCuotas = false;
        $this->resetQuotaFormData();
        // Limpiar fecha_fin cuando se cambia a clase (las clases no tienen fecha fin obligatoria)
        if ($this->formData['tipo'] === 'clase') {
            $this->formData['fecha_fin'] = '';
        }
    }

    public function updatedFormDataMembresiaId()
    {
        $this->membresiaPermiteCuotas = false;

        if ($this->formData['membresia_id']) {
            $membresia = \App\Models\Core\Membresia::find($this->formData['membresia_id']);
            if ($membresia) {
                $this->formData['precio_lista'] = $membresia->precio_base;
                $this->calculatePrecioFinal();
                $this->membresiaPermiteCuotas = (bool) $membresia->permite_cuotas;

                if ($this->membresiaPermiteCuotas) {
                    $this->formData['numero_cuotas'] = $membresia->numero_cuotas_default;
                    $this->formData['frecuencia_cuotas'] = $membresia->frecuencia_cuotas_default ?: 'mensual';
                    $this->formData['cuota_inicial_monto'] = $this->resolverCuotaInicialDefault($membresia, (float) $this->formData['precio_final']);
                    $this->formData['fecha_inicio_plan_cuotas'] = $this->formData['fecha_inicio'] ?: now()->format('Y-m-d');
                } else {
                    $this->resetQuotaFormData();
                }

                if ($this->formData['fecha_inicio']) {
                    $fechaInicio = \Carbon\Carbon::parse($this->formData['fecha_inicio']);
                    $this->formData['fecha_fin'] = $fechaInicio->copy()->addDays($membresia->duracion_dias ?? 30)->format('Y-m-d');
                }
            }
        } else {
            $this->resetQuotaFormData();
        }
    }

    public function updatedFormDataClaseId()
    {
        if ($this->formData['clase_id']) {
            $clase = \App\Models\Core\Clase::find($this->formData['clase_id']);
            if ($clase) {
                $this->formData['precio_lista'] = $clase->obtenerPrecio();
                if ($clase->tipo === 'paquete' && $clase->sesiones_paquete) {
                    $this->formData['sesiones_totales'] = $clase->sesiones_paquete;
                }
                $this->calculatePrecioFinal();
            }
        }
    }

    public function updatedFormDataPrecioLista()
    {
        $this->calculatePrecioFinal();
    }

    public function updatedFormDataDescuentoMonto()
    {
        $this->calculatePrecioFinal();
    }

    protected function calculatePrecioFinal()
    {
        $precioLista = (float) ($this->formData['precio_lista'] ?? 0);
        $descuento = (float) ($this->formData['descuento_monto'] ?? 0);
        $this->formData['precio_final'] = max(0, $precioLista - $descuento);
    }

    public function updatedFormDataModalidadPago(): void
    {
        if ($this->formData['modalidad_pago'] !== 'cuotas') {
            $this->resetQuotaFormData(keepModalidad: true);
        }
    }

    public function updatedFormDataFechaInicio()
    {
        if ($this->formData['fecha_inicio']) {
            if (! $this->formData['fecha_inicio_plan_cuotas']) {
                $this->formData['fecha_inicio_plan_cuotas'] = $this->formData['fecha_inicio'];
            }

            if ($this->formData['tipo'] === 'membresia' && $this->formData['membresia_id']) {
                $membresia = \App\Models\Core\Membresia::find($this->formData['membresia_id']);
                if ($membresia) {
                    $fechaInicio = \Carbon\Carbon::parse($this->formData['fecha_inicio']);
                    $this->formData['fecha_fin'] = $fechaInicio->copy()->addDays($membresia->duracion_dias)->format('Y-m-d');
                }
            } elseif ($this->formData['tipo'] === 'clase') {
                // Para clases, limpiar fecha_fin ya que no es obligatoria
                $this->formData['fecha_fin'] = '';
            }
        }
    }

    public function save()
    {
        $this->authorize($this->clienteMatriculaId ? 'cliente-matriculas.update' : 'cliente-matriculas.create');
        try {
            if (!$this->selectedClienteId) {
                $this->flashToast('error', 'Debes seleccionar un cliente primero');
                return;
            }

            $data = $this->mapFormToData();
            $data['cliente_id'] = $this->selectedClienteId;

            if ($this->clienteMatriculaId) {
                $matriculaActual = $this->service->find($this->clienteMatriculaId);
                $eraCongeladaYAhoraActiva = $matriculaActual
                    && $matriculaActual->tipo === 'membresia'
                    && $matriculaActual->estado === 'congelada'
                    && ($this->formData['estado'] ?? '') === 'activa';

                $this->service->update($this->clienteMatriculaId, $data);

                if ($eraCongeladaYAhoraActiva) {
                    $this->flashToast('success', 'Membresía activada correctamente. La fecha de inicio se actualizó a hoy. Si el cliente tenía otra membresía activa, esta ha pasado a estado congelada.');
                } else {
                    $this->flashToast('success', 'Matrícula actualizada correctamente');
                }
            } else {
                if ($this->renovandoMatriculaId) {
                    $matriculaVigente = $this->service->find($this->renovandoMatriculaId);
                    if ($matriculaVigente && $matriculaVigente->fecha_fin && \Carbon\Carbon::parse($matriculaVigente->fecha_fin)->gte(\Carbon\Carbon::today())) {
                        $this->formData['estado'] = 'congelada';
                    } else {
                        $this->formData['estado'] = 'activa';
                    }
                    $data = $this->mapFormToData();
                    $data['cliente_id'] = $this->selectedClienteId;
                }
                $this->service->create($data);
                if ($this->renovandoMatriculaId) {
                    $this->flashToast('success', $this->formData['estado'] === 'congelada'
                        ? 'Membresía renovada. Quedará congelada hasta que termine la membresía actual.'
                        : 'Membresía renovada y activa correctamente.');
                    $this->renovandoMatriculaId = null;
                } else {
                    $this->flashToast('success', 'Matrícula creada correctamente');
                }
            }

            \App\Models\Core\Cliente::where('id', $this->selectedClienteId)->update(['estado_cliente' => 'activo']);

            $this->closeModal();
            $this->resetPage();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->handleValidationErrors($e);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete()
    {
        $this->authorize('cliente-matriculas.delete');
        try {
            $this->service->delete($this->clienteMatriculaId);
            $this->flashToast('success', 'Matrícula eliminada correctamente');
            $this->closeModal();
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function mapClienteMatriculaToForm(ClienteMatricula $clienteMatricula): void
    {
        $this->formData = [
            'tipo' => $clienteMatricula->tipo,
            'membresia_id' => $clienteMatricula->membresia_id,
            'clase_id' => $clienteMatricula->clase_id,
            'fecha_matricula' => $clienteMatricula->fecha_matricula?->format('Y-m-d') ?? '',
            'fecha_inicio' => $clienteMatricula->fecha_inicio->format('Y-m-d'),
            'fecha_fin' => $clienteMatricula->fecha_fin ? $clienteMatricula->fecha_fin->format('Y-m-d') : '',
            'estado' => $clienteMatricula->estado,
            'precio_lista' => $clienteMatricula->precio_lista,
            'descuento_monto' => $clienteMatricula->descuento_monto,
            'precio_final' => $clienteMatricula->precio_final,
            'modalidad_pago' => $clienteMatricula->modalidad_pago ?? 'contado',
            'cuota_inicial_monto' => (float) ($clienteMatricula->cuota_inicial_monto ?? 0),
            'numero_cuotas' => $clienteMatricula->installmentPlan?->numero_cuotas,
            'frecuencia_cuotas' => $clienteMatricula->installmentPlan?->frecuencia ?? 'mensual',
            'fecha_inicio_plan_cuotas' => $clienteMatricula->installmentPlan?->fecha_inicio?->format('Y-m-d') ?? '',
            'asesor_id' => $clienteMatricula->asesor_id,
            'canal_venta' => $clienteMatricula->canal_venta ?? 'presencial',
            'fechas_congelacion' => $clienteMatricula->fechas_congelacion ?? [],
            'motivo_cancelacion' => $clienteMatricula->motivo_cancelacion ?? '',
            'sesiones_totales' => $clienteMatricula->sesiones_totales,
            'sesiones_usadas' => $clienteMatricula->sesiones_usadas ?? 0,
        ];

        $this->membresiaPermiteCuotas = (bool) ($clienteMatricula->membresia?->permite_cuotas ?? false);
    }

    protected function mapFormToData(): array
    {
        $data = [
            'tipo' => $this->formData['tipo'],
            'fecha_matricula' => $this->formData['fecha_matricula'],
            'fecha_inicio' => $this->formData['fecha_inicio'],
            'fecha_fin' => ($this->formData['tipo'] === 'clase') ? null : ($this->formData['fecha_fin'] ?: null),
            'estado' => $this->formData['estado'],
            'precio_lista' => $this->formData['precio_lista'],
            'descuento_monto' => $this->formData['descuento_monto'] ?? 0,
            'precio_final' => $this->formData['precio_final'],
            'asesor_id' => $this->formData['asesor_id'] ?: auth()->id(),
            'canal_venta' => $this->formData['canal_venta'] ?: null,
            'fechas_congelacion' => $this->formData['fechas_congelacion'] ?: null,
            'motivo_cancelacion' => $this->formData['motivo_cancelacion'] ?: null,
        ];

        if ($this->formData['tipo'] === 'membresia') {
            $data['membresia_id'] = $this->formData['membresia_id'];
            $data['clase_id'] = null;

            if (! $this->clienteMatriculaId) {
                $data['modalidad_pago'] = $this->formData['modalidad_pago'] ?? 'contado';

                if (($this->formData['modalidad_pago'] ?? 'contado') === 'cuotas') {
                    $data['cuota_inicial_monto'] = (float) ($this->formData['cuota_inicial_monto'] ?? 0);
                    $data['numero_cuotas'] = $this->formData['numero_cuotas'] ?: null;
                    $data['frecuencia_cuotas'] = $this->formData['frecuencia_cuotas'] ?: null;
                    $data['fecha_inicio_plan_cuotas'] = $this->formData['fecha_inicio_plan_cuotas'] ?: $this->formData['fecha_inicio'];
                }
            }
        } else {
            $data['clase_id'] = $this->formData['clase_id'];
            $data['membresia_id'] = null;
            $data['sesiones_totales'] = $this->formData['sesiones_totales'] ?? null;
            $data['sesiones_usadas'] = $this->formData['sesiones_usadas'] ?? 0;
        }

        return $data;
    }

    protected function resetForm(): void
    {
        $this->clienteMatriculaId = null;
        $this->renovandoMatriculaId = null;
        $this->membresiaPermiteCuotas = false;
        $this->formData = [
            'tipo' => $this->activeTab === 'membresias' ? 'membresia' : 'clase',
            'membresia_id' => '',
            'clase_id' => '',
            'fecha_matricula' => now()->format('Y-m-d'),
            'fecha_inicio' => '',
            'fecha_fin' => '',
            'estado' => 'activa',
            'precio_lista' => 0.00,
            'descuento_monto' => 0.00,
            'precio_final' => 0.00,
            'modalidad_pago' => 'contado',
            'cuota_inicial_monto' => 0.00,
            'numero_cuotas' => null,
            'frecuencia_cuotas' => 'mensual',
            'fecha_inicio_plan_cuotas' => '',
            'asesor_id' => auth()->id(),
            'canal_venta' => 'presencial',
            'fechas_congelacion' => [],
            'motivo_cancelacion' => '',
            'sesiones_totales' => null,
            'sesiones_usadas' => 0,
        ];
    }

    protected function resetQuotaFormData(bool $keepModalidad = false): void
    {
        if (! $keepModalidad) {
            $this->formData['modalidad_pago'] = 'contado';
        }

        $this->formData['cuota_inicial_monto'] = 0.00;
        $this->formData['numero_cuotas'] = null;
        $this->formData['frecuencia_cuotas'] = 'mensual';
        $this->formData['fecha_inicio_plan_cuotas'] = $this->formData['fecha_inicio'] ?: now()->format('Y-m-d');
    }

    protected function resolverCuotaInicialDefault(\App\Models\Core\Membresia $membresia, float $precioFinal): float
    {
        if ($membresia->cuota_inicial_monto !== null) {
            return (float) $membresia->cuota_inicial_monto;
        }

        if ($membresia->cuota_inicial_porcentaje !== null) {
            return round($precioFinal * ((float) $membresia->cuota_inicial_porcentaje / 100), 2);
        }

        return 0.0;
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
        $matriculas = collect([]);

        if ($this->selectedClienteId) {
            $filtros = [];
            if ($this->estadoFilter) {
                $filtros['estado'] = $this->estadoFilter;
            }
            if ($this->activeTab === 'membresias') {
                $filtros['tipo'] = 'membresia';
            } else {
                $filtros['tipo'] = 'clase';
            }

            $matriculas = $this->service->getByCliente(
                $this->selectedClienteId,
                $filtros,
                $this->perPage
            );
        }

        $membresiasActivas = collect([]);
        $clasesActivas = collect([]);

        if ($this->modalState['create']) {
            if ($this->formData['tipo'] === 'membresia') {
                $membresiasActivas = $this->service->getMembresiasActivas();
            } else {
                $clasesActivas = $this->service->getClasesActivas();
            }
        }

        $matriculaMembresiasProximasAVencer = $this->service->getMembresiasProximasAVencer(30, 20);

        return view('livewire.cliente-matriculas.cliente-matricula-live', [
            'matriculas' => $matriculas,
            'membresiasActivas' => $membresiasActivas,
            'clasesActivas' => $clasesActivas,
            'matriculaMembresiasProximasAVencer' => $matriculaMembresiasProximasAVencer,
        ]);
    }
}
