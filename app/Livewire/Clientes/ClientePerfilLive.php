<?php

namespace App\Livewire\Clientes;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Pago;
use App\Services\AsistenciaService;
use App\Services\ClientEnrollmentService;
use App\Services\ClienteService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ClientePerfilLive extends Component
{
    use FlashesToast;

    public string $clienteSearch = '';

    public Collection $clientes;

    public ?int $selectedClienteId = null;

    public ?Cliente $selectedCliente = null;

    public bool $isSearching = false;

    public string $tabActiva = 'membresias';

    public $membresiaActiva = null;

    public array $asistenciasRecientes = [];

    public array $estadisticasAsistencia = [];

    public array $validacionAcceso = [];

    public float $saldoPendiente = 0.0;

    public array $historialMembresias = [];

    public array $historialClases = [];

    public array $pagosRecientes = [];

    protected AsistenciaService $asistenciaService;

    protected ClienteService $clienteService;

    protected ClientEnrollmentService $clientEnrollmentService;

    public function boot(
        AsistenciaService $asistenciaService,
        ClienteService $clienteService,
        ClientEnrollmentService $clientEnrollmentService
    ): void {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clientEnrollmentService = $clientEnrollmentService;
    }

    public function mount(?Cliente $cliente = null): void
    {
        $this->authorize('clientes.view');
        $this->clientes = collect([]);

        if ($cliente) {
            $this->selectCliente($cliente->id);
            return;
        }

        $clienteId = request()->integer('cliente');
        if ($clienteId > 0) { // Compatibilidad temporal con enlaces anteriores.
            $this->selectCliente($clienteId);
        }
    }

    public function updatingClienteSearch($value): void
    {
        $this->isSearching = true;

        if ($this->selectedCliente) {
            $nombreCompleto = trim($this->selectedCliente->nombres . ' ' . $this->selectedCliente->apellidos);
            $valorTrim = trim((string) $value);

            if ($valorTrim !== '' && $valorTrim !== $nombreCompleto) {
                $this->clearClienteSelection();
                $this->clienteSearch = $valorTrim;
            }
        }
    }

    public function updatedClienteSearch(): void
    {
        $this->searchClientes();
    }

    public function searchClientes(): void
    {
        $searchTerm = trim($this->clienteSearch);

        if (strlen($searchTerm) >= 2) {
            $this->clientes = $this->clienteService->quickSearch($searchTerm, 10);
        } else {
            $this->clientes = collect([]);
        }

        $this->isSearching = false;
    }

    public function selectCliente(int $clienteId): void
    {
        $cliente = $this->clienteService->find($clienteId);
        if (! $cliente) {
            $this->flashToast('error', 'Cliente no encontrado.');
            return;
        }

        $this->selectedClienteId = $clienteId;
        $this->selectedCliente = $cliente;
        $this->clienteSearch = trim($cliente->nombres . ' ' . $cliente->apellidos);
        $this->clientes = collect([]);
        $this->isSearching = false;

        $this->refreshSelectedClienteContext($clienteId);
    }

    public function clearClienteSelection(): void
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->tabActiva = 'membresias';
        $this->resetearSeleccion();
    }

    public function setTab(string $tab): void
    {
        $this->tabActiva = in_array($tab, ['membresias', 'matriculas'], true) ? $tab : 'membresias';
    }

    protected function refreshSelectedClienteContext(int $clienteId): void
    {
        $this->selectedCliente = $this->clienteService->find($clienteId);

        $activeEnrollment = $this->clientEnrollmentService->resolveActiveEnrollment($clienteId);
        $this->membresiaActiva = $activeEnrollment['source_model'] ?? null;
        $this->saldoPendiente = (float) ($activeEnrollment['saldo_pendiente'] ?? 0);
        $this->asistenciasRecientes = $this->asistenciaService->obtenerAsistenciasRecientes($clienteId, 5)->all();
        $this->validacionAcceso = $this->membresiaActiva
            ? $this->asistenciaService->validarAccesoPorHorario($this->membresiaActiva)
            : [];

        $this->estadisticasAsistencia = $this->membresiaActiva
            ? $this->asistenciaService->obtenerEstadisticasAsistencia($clienteId, $this->membresiaActiva->id)
            : [
                'total_asistencias' => 0,
                'asistencias_completas' => 0,
                'asistencias_pendientes' => 0,
                'total_sesiones' => 0,
                'porcentaje_efectividad' => 0,
            ];

        $history = $this->clientEnrollmentService->resolveCommercialHistory($clienteId);
        $this->historialMembresias = $history['memberships']->all();
        $this->historialClases = $history['classes']->all();

        if (! $this->membresiaActiva && ! empty($this->historialMembresias)) {
            $this->membresiaActiva = $this->clientEnrollmentService
                ->resolveLatestActiveEnrollmentFromHistory(collect($this->historialMembresias));
        }

        $this->pagosRecientes = Pago::query()
            ->where('cliente_id', $clienteId)
            ->with(['registradoPor', 'clienteMembresia.membresia', 'clienteMatricula.clase', 'clienteMatricula.membresia'])
            ->orderByDesc('fecha_pago')
            ->limit(5)
            ->get()
            ->all();
    }

    public function getTipoRegistroHistorial($record): string
    {
        if ($record instanceof ClienteMatricula) {
            return $record->tipo === 'clase' ? 'clase' : 'membresia';
        }

        return $record instanceof ClienteMembresia ? 'membresia' : 'desconocido';
    }

    protected function resetearSeleccion(): void
    {
        $this->membresiaActiva = null;
        $this->asistenciasRecientes = [];
        $this->estadisticasAsistencia = [];
        $this->validacionAcceso = [];
        $this->saldoPendiente = 0.0;
        $this->historialMembresias = [];
        $this->historialClases = [];
        $this->pagosRecientes = [];
    }

    public function render()
    {
        return view('livewire.clientes.cliente-perfil-live');
    }
}
