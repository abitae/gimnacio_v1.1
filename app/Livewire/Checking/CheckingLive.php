<?php

namespace App\Livewire\Checking;

use App\Livewire\Concerns\FlashesToast;
use App\Services\AsistenciaService;
use App\Services\ClientEnrollmentService;
use App\Services\ClienteService;
use App\Services\DailyOperationsDebtService;
use Livewire\Component;

class CheckingLive extends Component
{
    use FlashesToast;

    public $clienteSearch = '';

    public $clientes;

    public $selectedClienteId = null;

    public $selectedCliente = null;

    public $isSearching = false;

    public $membresiaActiva = null;

    public $asistenciasRecientes = [];

    public $estadisticasAsistencia = [];

    public $validacionAcceso = [];

    public $saldoPendiente = 0.00;

    public $debtSummary = [];

    public $estadoOperacion = [];

    public $historialMembresias = [];

    public $historialClases = [];

    public $mostrarModalConfirmacion = false;

    public $asistenciaRegistrada = null;

    public $tipoRegistroModal = null;

    public $ingresoEnCurso = null;

    protected AsistenciaService $asistenciaService;

    protected ClienteService $clienteService;

    protected ClientEnrollmentService $clientEnrollmentService;

    protected DailyOperationsDebtService $dailyOperationsDebtService;

    public function boot(
        AsistenciaService $asistenciaService,
        ClienteService $clienteService,
        ClientEnrollmentService $clientEnrollmentService,
        DailyOperationsDebtService $dailyOperationsDebtService
    ) {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clientEnrollmentService = $clientEnrollmentService;
        $this->dailyOperationsDebtService = $dailyOperationsDebtService;
    }

    public function mount()
    {
        $this->authorize('checking.ver');
        $this->clientes = collect([]);
    }

    public function updatingClienteSearch($value)
    {
        $this->isSearching = true;

        if ($this->selectedCliente) {
            $nombreCompleto = $this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos;
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

    public function selectCliente($clienteId)
    {
        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $this->clienteService->find($clienteId);

        if ($this->selectedCliente) {
            $this->clienteSearch = $this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos;
        }

        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->refreshSelectedClienteContext((int) $clienteId);
    }

    public function clearClienteSelection()
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->resetearSeleccion();
    }

    public function registrarIngreso()
    {
        $this->authorize('checking.crear');

        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero.');

            return;
        }

        try {
            $validacion = $this->asistenciaService->validarIngreso($this->selectedClienteId);

            if (! $validacion['valido']) {
                $this->flashToast('error', $validacion['mensaje']);

                return;
            }

            $this->asistenciaRegistrada = $this->asistenciaService->registrarIngreso($this->selectedClienteId);
            $this->ingresoEnCurso = $this->asistenciaRegistrada;
            $this->tipoRegistroModal = 'ingreso';
            $this->mostrarModalConfirmacion = true;
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);

            $this->dispatch('checking-registro', clienteId: $this->selectedClienteId);
            session()->put('dashboard_last_cliente_id', $this->selectedClienteId);
            $this->flashToast('success', 'Ingreso registrado exitosamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function registrarSalida($asistenciaId)
    {
        $this->authorize('checking.editar');

        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Debes seleccionar un cliente primero.');

            return;
        }

        try {
            $asistencia = $this->asistenciaService->registrarSalida((int) $asistenciaId);
            $this->asistenciaRegistrada = $asistencia;
            $this->ingresoEnCurso = null;
            $this->tipoRegistroModal = 'salida';
            $this->mostrarModalConfirmacion = true;
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);

            $this->dispatch('checking-registro', clienteId: $this->selectedClienteId);
            session()->put('dashboard_last_cliente_id', $this->selectedClienteId);
            $this->flashToast('success', 'Salida registrada exitosamente.');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function cerrarModal()
    {
        $this->mostrarModalConfirmacion = false;
        $this->asistenciaRegistrada = null;
        $this->tipoRegistroModal = null;
        $this->clearClienteSelection();
    }

    protected function cargarHistorialComercial(int $clienteId): void
    {
        $history = $this->clientEnrollmentService->resolveCommercialHistory($clienteId);
        $this->historialMembresias = $history['memberships'];
        $this->historialClases = $history['classes'];

        if (! $this->membresiaActiva && $this->historialMembresias->isNotEmpty()) {
            $this->membresiaActiva = $this->clientEnrollmentService->resolveLatestActiveEnrollmentFromHistory($this->historialMembresias);
        }
    }

    protected function refreshSelectedClienteContext(int $clienteId): void
    {
        $activeEnrollment = $this->clientEnrollmentService->resolveActiveEnrollment($clienteId);
        $this->membresiaActiva = $activeEnrollment['source_model'] ?? null;
        $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($clienteId, 5);
        $this->ingresoEnCurso = $this->asistenciaService->obtenerIngresoEnCurso($clienteId);
        $this->estadisticasAsistencia = [];
        $this->validacionAcceso = [];
        $this->saldoPendiente = 0.00;

        if ($this->membresiaActiva) {
            $this->estadisticasAsistencia = $this->asistenciaService->obtenerEstadisticasAsistencia($clienteId, $this->membresiaActiva->id);
            $this->validacionAcceso = $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva);
            $this->saldoPendiente = (float) ($activeEnrollment['saldo_pendiente'] ?? 0);
        }

        $this->debtSummary = $this->dailyOperationsDebtService->summarizeCliente($clienteId);
        $this->estadoOperacion = $this->resolverEstadoOperacion();
        $this->cargarHistorialComercial($clienteId);
    }

    protected function resolverEstadoOperacion(): array
    {
        if (! $this->membresiaActiva) {
            return [
                'nivel' => 'bloqueado',
                'titulo' => 'Acceso bloqueado',
                'mensaje' => 'El cliente no tiene un plan activo para registrar ingreso.',
            ];
        }

        if (($this->debtSummary['tiene_deuda_vencida'] ?? false) === true) {
            return [
                'nivel' => 'alerta',
                'titulo' => 'Acceso permitido con alerta',
                'mensaje' => 'Tiene deuda vencida o cuotas pendientes. Deriva a cobranza si corresponde.',
            ];
        }

        if (($this->debtSummary['tiene_deuda'] ?? false) === true) {
            return [
                'nivel' => 'alerta',
                'titulo' => 'Acceso permitido con seguimiento',
                'mensaje' => 'Tiene saldo pendiente, pero no bloquea el acceso en la política actual.',
            ];
        }

        return [
            'nivel' => 'ok',
            'titulo' => 'Acceso permitido',
            'mensaje' => 'Cliente habilitado para ingresar.',
        ];
    }

    protected function resetearSeleccion()
    {
        $this->membresiaActiva = null;
        $this->asistenciasRecientes = [];
        $this->estadisticasAsistencia = [];
        $this->validacionAcceso = [];
        $this->saldoPendiente = 0.00;
        $this->debtSummary = [];
        $this->estadoOperacion = [];
        $this->historialMembresias = [];
        $this->historialClases = [];
        $this->ingresoEnCurso = null;
    }

    public function render()
    {
        return view('livewire.checking.checking-live');
    }
}
