<?php

namespace App\Livewire\Dashboard;

use App\Models\Core\Asistencia;
use App\Models\Core\Clase;
use App\Services\AsistenciaService;
use App\Services\ClientEnrollmentService;
use App\Services\ClienteService;
use Livewire\Attributes\On;
use Livewire\Component;

class DashboardLive extends Component
{
    public $selectedClienteId = null;

    protected ?int $lastSyncedLatestClienteId = null;

    public $selectedCliente = null;
    public $membresiaActiva = null;
    public $asistenciasRecientes = [];
    public $ingresoEnCurso = null;
    public $estadisticasAsistencia = [];
    public $validacionAcceso = [];
    public $saldoPendiente = 0.00;
    public $historialMembresias = [];
    public $historialClases = [];

    protected AsistenciaService $asistenciaService;
    protected ClienteService $clienteService;
    protected ClientEnrollmentService $clientEnrollmentService;

    public function boot(
        AsistenciaService $asistenciaService,
        ClienteService $clienteService,
        ClientEnrollmentService $clientEnrollmentService
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clientEnrollmentService = $clientEnrollmentService;
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

        $ultimaAsistencia = Asistencia::with('cliente')
            ->orderBy('fecha_hora_ingreso', 'desc')
            ->first();

        if ($ultimaAsistencia && $ultimaAsistencia->cliente_id) {
            $this->selectedClienteId = (int) $ultimaAsistencia->cliente_id;
            $this->cargarClienteSeleccionado();
        }
    }

    public function getHistorialAsistenciasProperty()
    {
        return Asistencia::with(['cliente', 'registradaPor'])
            ->orderBy('fecha_hora_ingreso', 'desc')
            ->limit(100)
            ->get();
    }

    public function getClasesProperty()
    {
        return Clase::where('estado', 'activo')
            ->with('instructor')
            ->orderBy('nombre')
            ->limit(12)
            ->get();
    }

    public function selectClienteFromRow(int $clienteId)
    {
        if ($clienteId <= 0) {
            return;
        }

        $this->selectedClienteId = $clienteId;
        $this->cargarClienteSeleccionado();
    }

    #[On('checking-registro')]
    public function onCheckingRegistro(int $clienteId)
    {
        $this->selectedClienteId = $clienteId;
        $this->cargarClienteSeleccionado();
    }

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
        if (! $this->selectedClienteId) {
            $this->clearClienteSelection();
            return;
        }

        $this->selectedCliente = $this->clienteService->find($this->selectedClienteId);
        if (! $this->selectedCliente) {
            $this->clearClienteSelection();
            return;
        }

        $activeEnrollment = $this->clientEnrollmentService->resolveActiveEnrollment($this->selectedClienteId);
        $this->membresiaActiva = $activeEnrollment['source_model'] ?? null;
        $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($this->selectedClienteId, 5);
        $this->ingresoEnCurso = $this->asistenciaService->obtenerIngresoEnCurso($this->selectedClienteId);

        if ($this->membresiaActiva) {
            $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia(
                $this->selectedClienteId,
                $this->membresiaActiva->id
            );
            $this->validacionAcceso = $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva);
            $this->saldoPendiente = (float) ($activeEnrollment['saldo_pendiente'] ?? 0);
        } else {
            $this->estadisticasAsistencia = [];
            $this->validacionAcceso = ['tiene_acceso' => false, 'mensaje' => 'Sin membresia activa.'];
            $this->saldoPendiente = 0.00;
        }

        $history = $this->clientEnrollmentService->resolveCommercialHistory($this->selectedClienteId, 10, 10);
        $this->historialMembresias = $history['memberships'];
        $this->historialClases = $history['classes'];
    }

    protected function syncSelectedClienteToLatestRecord(): void
    {
        $ultima = Asistencia::orderBy('fecha_hora_ingreso', 'desc')->first();
        if (! $ultima || ! $ultima->cliente_id) {
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
