<?php

namespace App\Livewire\Checking;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\ClienteMatricula;
use App\Services\AsistenciaService;
use App\Services\ClienteService;
use App\Services\ClienteMatriculaService;
use Livewire\Component;

class CheckingLive extends Component
{
    use FlashesToast;
    // Cliente search
    public $clienteSearch = '';
    public $clientes;
    public $selectedClienteId = null;
    public $selectedCliente = null;
    public $isSearching = false;

    // Información del cliente seleccionado
    public $membresiaActiva = null;
    public $asistenciasRecientes = [];
    public $estadisticasAsistencia = [];
    public $validacionAcceso = [];
    public $saldoPendiente = 0.00;
    public $historialMembresias = [];
    public $historialClases = [];
    public $mostrarModalConfirmacion = false;
    public $asistenciaRegistrada = null;
    /** 'ingreso' | 'salida' para el contenido del modal de confirmación */
    public $tipoRegistroModal = null;
    /** Ingreso en curso (sin salida): si existe, el próximo registro será salida */
    public $ingresoEnCurso = null;

    protected AsistenciaService $asistenciaService;
    protected ClienteService $clienteService;
    protected ClienteMatriculaService $clienteMatriculaService;

    public function boot(AsistenciaService $asistenciaService, ClienteService $clienteService, ClienteMatriculaService $clienteMatriculaService)
    {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clienteMatriculaService = $clienteMatriculaService;
    }

    public function mount()
    {
        $this->authorize('checking.view');
        $this->clientes = collect([]);
    }

    /**
     * Buscar clientes automáticamente cuando cambia la búsqueda
     */
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
                $this->resetearSeleccion();
            }
        }
    }

    public function updatedClienteSearch()
    {
        $this->searchClientes();
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

    /**
     * Seleccionar un cliente de la lista
     */
    public function selectCliente($clienteId)
    {
        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);
        if ($this->selectedCliente) {
            $this->clienteSearch = $this->selectedCliente->nombres . ' ' . $this->selectedCliente->apellidos;
        }
        $this->clientes = collect([]);
        $this->isSearching = false;

        // Cargar información adicional
        // Primero intentar obtener membresía activa desde ClienteMatricula
        $hoy = today();
        
        // Buscar en ClienteMatricula
        $matriculaActiva = ClienteMatricula::where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $hoy)
            ->where(function ($query) use ($hoy) {
                $query->whereNull('fecha_fin')
                    ->orWhere('fecha_fin', '>=', $hoy);
            })
            ->with(['membresia', 'cliente'])
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        // Si no hay en ClienteMatricula, buscar en ClienteMembresia (compatibilidad)
        if (!$matriculaActiva) {
            $this->membresiaActiva = $this->asistenciaService->obtenerMembresiaActiva($clienteId);
        } else {
            // Verificar que la relación membresia esté cargada
            if (!$matriculaActiva->relationLoaded('membresia')) {
                $matriculaActiva->load('membresia');
            }
            $this->membresiaActiva = $matriculaActiva;
        }

        $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($clienteId, 5);
        $this->ingresoEnCurso = $this->asistenciaService->obtenerIngresoEnCurso($clienteId);

        // Si hay membresía activa, cargar información adicional
        if ($this->membresiaActiva) {
            // Obtener estadísticas de asistencia
            $membresiaId = $this->membresiaActiva->id;
            $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia($clienteId, $membresiaId);
            
            // Validar acceso
            $this->validacionAcceso = $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva);
            
            // Calcular saldo pendiente
            if ($this->membresiaActiva instanceof ClienteMatricula) {
                $this->saldoPendiente = $this->clienteMatriculaService->obtenerSaldoPendiente($this->membresiaActiva->id);
            } else {
                $this->saldoPendiente = $this->calcularSaldoPendiente($this->membresiaActiva->id);
            }
        }

        // Historial de membresías y clases (todas, tengan o no deuda)
        $this->cargarHistorialMembresias($clienteId);
        $this->cargarHistorialClases($clienteId);
    }

    /**
     * Limpiar selección de cliente
     */
    public function clearClienteSelection()
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->resetearSeleccion();
    }

    /**
     * Registrar ingreso
     */
    public function registrarIngreso()
    {
        $this->authorize('checking.create');
        if (!$this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero.');
            return;
        }

        try {
            $validacion = $this->asistenciaService->validarIngreso($this->selectedClienteId);

            if (!$validacion['valido']) {
                $this->flashToast('error', $validacion['mensaje']);
                return;
            }

            $this->asistenciaRegistrada = $this->asistenciaService->registrarIngreso($this->selectedClienteId);
            $this->ingresoEnCurso = $this->asistenciaRegistrada;
            $this->tipoRegistroModal = 'ingreso';
            $this->mostrarModalConfirmacion = true;

            // Actualizar datos
            // Buscar membresía activa desde ClienteMatricula primero
            $hoy = today();
            $matriculaActiva = ClienteMatricula::where('cliente_id', $this->selectedClienteId)
                ->where('tipo', 'membresia')
                ->where('estado', 'activa')
                ->where('fecha_inicio', '<=', $hoy)
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_fin')
                        ->orWhere('fecha_fin', '>=', $hoy);
                })
                ->with(['membresia', 'cliente'])
                ->orderBy('fecha_inicio', 'desc')
                ->first();

            if (!$matriculaActiva) {
                $this->membresiaActiva = $this->asistenciaService->obtenerMembresiaActiva($this->selectedClienteId);
            } else {
                $this->membresiaActiva = $matriculaActiva;
            }
            
            $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($this->selectedClienteId, 5);
            
            // Actualizar estadísticas y validación si hay membresía activa
            if ($this->membresiaActiva) {
                $membresiaId = $this->membresiaActiva->id;
                $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia($this->selectedClienteId, $membresiaId);
                $this->validacionAcceso = $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva);
                
                // Calcular saldo pendiente
                if ($this->membresiaActiva instanceof ClienteMatricula) {
                    $this->saldoPendiente = $this->clienteMatriculaService->obtenerSaldoPendiente($this->membresiaActiva->id);
                } else {
                    $this->saldoPendiente = $this->calcularSaldoPendiente($this->membresiaActiva->id);
                }
            }
            
            $this->dispatch('checking-registro', clienteId: $this->selectedClienteId);
            session()->put('dashboard_last_cliente_id', $this->selectedClienteId);
            $this->flashToast('success', 'Ingreso registrado exitosamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Registrar salida (cuando el cliente tiene un ingreso en curso)
     */
    public function registrarSalida($asistenciaId)
    {
        $this->authorize('checking.update');
        if (!$this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero.');
            return;
        }

        try {
            $asistencia = $this->asistenciaService->registrarSalida((int) $asistenciaId);
            $this->asistenciaRegistrada = $asistencia;
            $this->ingresoEnCurso = null;
            $this->tipoRegistroModal = 'salida';
            $this->mostrarModalConfirmacion = true;
            $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($this->selectedClienteId, 5);
            if ($this->membresiaActiva) {
                $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia(
                    $this->selectedClienteId,
                    $this->membresiaActiva->id
                );
            }
            $this->dispatch('checking-registro', clienteId: $this->selectedClienteId);
            session()->put('dashboard_last_cliente_id', $this->selectedClienteId);
            $this->flashToast('success', 'Salida registrada exitosamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * Cerrar modal de confirmación
     */
    /**
     * Cerrar modal de confirmación y reiniciar todas las variables
     */
    public function cerrarModal()
    {
        $this->mostrarModalConfirmacion = false;
        $this->asistenciaRegistrada = null;
        $this->tipoRegistroModal = null;
        $this->clearClienteSelection();
    }

    /**
     * Calcular saldo pendiente de la membresía
     */
    protected function calcularSaldoPendiente(int $clienteMembresiaId): float
    {
        $clienteMembresiaService = app(\App\Services\ClienteMembresiaService::class);
        return $clienteMembresiaService->obtenerSaldoPendiente($clienteMembresiaId);
    }

    /**
     * Cargar historial de membresías (todas, tengan o no deuda)
     */
    protected function cargarHistorialMembresias(int $clienteId): void
    {
        // Obtener membresías desde ClienteMatricula
        $matriculasMembresias = ClienteMatricula::with(['membresia', 'pagos'])
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // También incluir membresías antiguas de ClienteMembresia para compatibilidad
        $membresiasAntiguas = ClienteMembresia::with(['membresia', 'pagos'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_inicio', 'desc')
            ->get();

        // Combinar ambas colecciones
        $this->historialMembresias = $matriculasMembresias->concat($membresiasAntiguas)->sortByDesc('fecha_inicio')->values();
        
        // Si no se encontró membresía activa pero hay membresías en el historial,
        // intentar encontrar una activa en el historial
        if (!$this->membresiaActiva && $this->historialMembresias->isNotEmpty()) {
            $hoy = today();
            $membresiaActivaEnHistorial = $this->historialMembresias->first(function ($membresia) use ($hoy) {
                return $membresia->estado === 'activa' 
                    && $membresia->fecha_inicio <= $hoy
                    && ($membresia->fecha_fin === null || $membresia->fecha_fin >= $hoy);
            });
            
            if ($membresiaActivaEnHistorial) {
                // Cargar la relación membresia si no está cargada
                if (!$membresiaActivaEnHistorial->relationLoaded('membresia')) {
                    $membresiaActivaEnHistorial->load('membresia');
                }
                $this->membresiaActiva = $membresiaActivaEnHistorial;
                
                // Cargar información adicional
                $membresiaId = $this->membresiaActiva->id;
                $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia($clienteId, $membresiaId);
                $this->validacionAcceso = $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva);
                
                if ($this->membresiaActiva instanceof ClienteMatricula) {
                    $this->saldoPendiente = $this->clienteMatriculaService->obtenerSaldoPendiente($this->membresiaActiva->id);
                } else {
                    $this->saldoPendiente = $this->calcularSaldoPendiente($this->membresiaActiva->id);
                }
            }
        }
    }

    /**
     * Cargar historial de clases (todas, tengan o no deuda)
     */
    protected function cargarHistorialClases(int $clienteId): void
    {
        $this->historialClases = ClienteMatricula::with(['clase', 'pagos'])
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'clase')
            ->orderBy('fecha_inicio', 'desc')
            ->get();
    }

    /**
     * Resetear selección
     */
    protected function resetearSeleccion()
    {
        $this->membresiaActiva = null;
        $this->asistenciasRecientes = [];
        $this->estadisticasAsistencia = [];
        $this->validacionAcceso = [];
        $this->saldoPendiente = 0.00;
        $this->historialMembresias = [];
        $this->historialClases = [];
        $this->ingresoEnCurso = null;
    }

    public function render()
    {
        return view('livewire.checking.checking-live');
    }
}
