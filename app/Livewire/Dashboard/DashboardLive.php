<?php

namespace App\Livewire\Dashboard;

use App\Models\Core\Asistencia;
use App\Models\Core\Clase;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Services\AsistenciaService;
use App\Services\ClienteService;
use App\Services\ClienteMatriculaService;
use Livewire\Component;
use Livewire\Attributes\On;

class DashboardLive extends Component
{
    /** ID del cliente seleccionado (desde tabla o evento ingreso/salida) */
    public $selectedClienteId = null;

    /** Cliente ID del último registro de la tabla la última vez que sincronizamos (para no pisar selección manual) */
    protected ?int $lastSyncedLatestClienteId = null;

    /** Cliente cargado para el panel izquierdo */
    public $selectedCliente = null;

    /** Datos derivados del cliente seleccionado */
    public $membresiaActiva = null;
    public $asistenciasRecientes = [];
    public $ingresoEnCurso = null;
    public $estadisticasAsistencia = [];
    public $validacionAcceso = [];
    public $saldoPendiente = 0.00;
    /** @var \Illuminate\Support\Collection */
    public $historialMembresias = [];
    /** @var \Illuminate\Support\Collection */
    public $historialClases = [];

    protected AsistenciaService $asistenciaService;
    protected ClienteService $clienteService;
    protected ClienteMatriculaService $clienteMatriculaService;

    public function boot(
        AsistenciaService $asistenciaService,
        ClienteService $clienteService,
        ClienteMatriculaService $clienteMatriculaService
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clienteMatriculaService = $clienteMatriculaService;
    }

    public function mount()
    {
        $this->historialMembresias = collect([]);
        $this->historialClases = collect([]);
        $lastClienteId = session()->pull('dashboard_last_cliente_id');
        if ($lastClienteId) {
            $this->selectedClienteId = (int) $lastClienteId;
            $this->cargarClienteSeleccionado();
            return;
        }
        // Si no vino desde Checking, seleccionar automáticamente el cliente del último registro del historial
        $ultimaAsistencia = Asistencia::with('cliente')
            ->orderBy('fecha_hora_ingreso', 'desc')
            ->first();
        if ($ultimaAsistencia && $ultimaAsistencia->cliente_id) {
            $this->selectedClienteId = (int) $ultimaAsistencia->cliente_id;
            $this->cargarClienteSeleccionado();
        }
    }

    /**
     * Historial global de ingresos/salidas para la tabla (últimas 100).
     */
    public function getHistorialAsistenciasProperty()
    {
        return Asistencia::with(['cliente', 'registradaPor'])
            ->orderBy('fecha_hora_ingreso', 'desc')
            ->limit(100)
            ->get();
    }

    /**
     * Clases activas para el panel central (próximas o del día).
     */
    public function getClasesProperty()
    {
        return Clase::where('estado', 'activo')
            ->with('instructor')
            ->orderBy('nombre')
            ->limit(12)
            ->get();
    }

    /**
     * Seleccionar cliente al hacer clic en una fila de la tabla.
     */
    public function selectClienteFromRow(int $clienteId)
    {
        if ($clienteId <= 0) {
            return;
        }
        $this->selectedClienteId = $clienteId;
        $this->cargarClienteSeleccionado();
    }

    /**
     * Listener: cuando en Checking se registra ingreso o salida, actualizar cliente seleccionado y tabla.
     */
    #[On('checking-registro')]
    public function onCheckingRegistro(int $clienteId)
    {
        $this->selectedClienteId = $clienteId;
        $this->cargarClienteSeleccionado();
    }

    /**
     * Cerrar panel de información del cliente.
     */
    public function clearClienteSelection()
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->membresiaActiva = null;
        $this->asistenciasRecientes = [];
        $this->ingresoEnCurso = null;
        $this->estadisticasAsistencia = [];
        $this->validacionAcceso = [];
        $this->saldoPendiente = 0.00;
        $this->historialMembresias = collect([]);
        $this->historialClases = collect([]);
    }

    protected function cargarClienteSeleccionado(): void
    {
        if (!$this->selectedClienteId) {
            $this->clearClienteSelection();
            return;
        }

        $this->selectedCliente = $this->clienteService->find($this->selectedClienteId);
        if (!$this->selectedCliente) {
            $this->clearClienteSelection();
            return;
        }

        $hoy = today();
        $this->membresiaActiva = \App\Models\Core\ClienteMatricula::where('cliente_id', $this->selectedClienteId)
            ->where('tipo', 'membresia')
            ->where('estado', 'activa')
            ->where('fecha_inicio', '<=', $hoy)
            ->where(function ($q) use ($hoy) {
                $q->whereNull('fecha_fin')->orWhere('fecha_fin', '>=', $hoy);
            })
            ->with(['membresia', 'cliente'])
            ->orderBy('fecha_inicio', 'desc')
            ->first();

        if (!$this->membresiaActiva) {
            $this->membresiaActiva = $this->asistenciaService->obtenerMembresiaActiva($this->selectedClienteId);
        }

        $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($this->selectedClienteId, 5);
        $this->ingresoEnCurso = $this->asistenciaService->obtenerIngresoEnCurso($this->selectedClienteId);

        if ($this->membresiaActiva) {
            $membresiaId = $this->membresiaActiva->id;
            $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia(
                $this->selectedClienteId,
                $membresiaId
            );
            $this->validacionAcceso = $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva);
            $this->saldoPendiente = $this->membresiaActiva instanceof \App\Models\Core\ClienteMatricula
                ? $this->clienteMatriculaService->obtenerSaldoPendiente($this->membresiaActiva->id)
                : app(\App\Services\ClienteMembresiaService::class)->obtenerSaldoPendiente($this->membresiaActiva->id);
        } else {
            $this->estadisticasAsistencia = [];
            $this->validacionAcceso = ['tiene_acceso' => false, 'mensaje' => 'Sin membresía activa.'];
            $this->saldoPendiente = 0.00;
        }

        $this->cargarHistorialMembresias($this->selectedClienteId);
        $this->cargarHistorialClases($this->selectedClienteId);
    }

    protected function cargarHistorialMembresias(int $clienteId): void
    {
        $matriculas = ClienteMatricula::with(['membresia'])
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'membresia')
            ->orderBy('fecha_inicio', 'desc')
            ->limit(10)
            ->get();
        $antiguas = ClienteMembresia::with(['membresia'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_inicio', 'desc')
            ->limit(10)
            ->get();
        $this->historialMembresias = $matriculas->concat($antiguas)->sortByDesc('fecha_inicio')->take(10)->values();
    }

    protected function cargarHistorialClases(int $clienteId): void
    {
        $this->historialClases = ClienteMatricula::with(['clase'])
            ->where('cliente_id', $clienteId)
            ->where('tipo', 'clase')
            ->orderBy('fecha_inicio', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Sincroniza el cliente seleccionado con el último registro de la tabla solo cuando
     * el "último registro" cambia (nuevo ingreso/salida). Así no se pisa una selección manual.
     */
    protected function syncSelectedClienteToLatestRecord(): void
    {
        $ultima = Asistencia::orderBy('fecha_hora_ingreso', 'desc')->first();
        if (!$ultima || !$ultima->cliente_id) {
            return;
        }
        $latestClienteId = (int) $ultima->cliente_id;
        if ($this->lastSyncedLatestClienteId === $latestClienteId) {
            return;
        }
        $this->lastSyncedLatestClienteId = $latestClienteId;
        $this->selectedClienteId = $latestClienteId;
        $this->cargarClienteSeleccionado();
    }

    public function render()
    {
        $this->syncSelectedClienteToLatestRecord();
        return view('livewire.dashboard.dashboard-live');
    }
}
