<?php

namespace App\Livewire\Clientes;

use App\Data\Cliente\ClienteCommercialSummary;
use App\Data\Cliente\ClienteProfileContext;
use App\Livewire\Clientes\Concerns\ManagesClienteCommercialTab;
use App\Livewire\Clientes\Concerns\ManagesClienteCrudAndPhoto;
use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\LogsLivewireErrors;
use App\Livewire\Concerns\ManagesClienteMatriculaForm;
use App\Livewire\Concerns\ManagesCuotaPagoModal;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteFidelizacionMensaje;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\Pago;
use App\Models\Core\PaymentMethod;
use App\Models\Core\RentableSpace;
use App\Models\Core\Rental;
use App\Models\User;
use App\Services\AsistenciaService;
use App\Services\Cliente\ClienteProfileContextService;
use App\Services\ClienteMatriculaService;
use App\Services\ClientEnrollmentService;
use App\Services\ClienteService;
use App\Services\ClientWellnessService;
use App\Services\EnrollmentInstallmentService;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Ficha 360 del cliente (shell + traits).
 *
 * Tabs: membresias/matriculas (T comercial), finanzas (R), asistencias (T atajo checking),
 * reservas (T), fidelización (T), CRM (L → crm.clientes.etiquetas).
 */
class ClientePerfilLive extends Component
{
    use FlashesToast;
    use LogsLivewireErrors;
    use ManagesClienteCommercialTab;
    use ManagesClienteCrudAndPhoto;
    use ManagesClienteMatriculaForm;
    use ManagesCuotaPagoModal;

    public string $clienteSearch = '';

    public string $codigoSearch = '';

    public string $responsableFilter = '';

    public Collection $clientes;

    public ?int $selectedClienteId = null;

    public ?Cliente $selectedCliente = null;

    public bool $isSearching = false;

    public string $tabActiva = 'membresias';

    public bool $perfilClienteMinimizado = false;

    public $membresiaActiva = null;

    public array $asistenciasRecientes = [];

    public array $estadisticasAsistencia = [];

    public array $validacionAcceso = [];

    public $ingresoEnCurso = null;

    public float $saldoPendiente = 0.0;

    public float $deudaMembresiaPendiente = 0.0;

    /** Suma de saldos pendientes de ventas a crédito / producto (`client_debts`). */
    public float $deudaProductoPendiente = 0.0;

    public array $operacionDiariaDebtSummary = [];

    /** @var array<int, array<string, mixed>> */
    public array $deudasVencidasPerfil = [];

    public bool $mostrarModalDeudaVencida = false;

    public bool $abrirDeudaVencidaAlIniciar = false;

    public array $historialMembresias = [];

    public array $historialClases = [];

    public array $pagosRecientes = [];

    /** @var array<int, \App\Models\Core\Rental> */
    public array $reservasEspacios = [];

    public $cuotasModalInstallments;

    public $paymentMethods;

    public $rentableSpaces;

    public $trainers;

    public $responsables;

    public $membresiasActivas;

    public $clasesActivas;

    public bool $commercialTabDataLoaded = false;

    public bool $usesLegacyMembresiasHistory = false;

    /** @var array<string, mixed> */
    public array $crmSummary = [];

    public bool $mostrarModalTicketPago = false;

    public ?int $pagoTicketPreviewId = null;

    /** @var 'cuotas_pendientes'|'pagos' */
    public string $perfilFinanzasTab = 'pagos';

    public bool $reservaModalAbierto = false;

    public ?int $editingRentalId = null;

    public bool $trainerModalAbierto = false;

    public $trainerAsignacionId = null;

    public array $reservaForm = [
        'rentable_space_id' => null,
        'fecha' => '',
        'hora_inicio' => '',
        'hora_fin' => '',
        'precio' => '',
        'estado' => 'reservado',
        'observaciones' => '',
    ];

    /** @var array<int, ClienteFidelizacionMensaje> */
    public array $fidelizacionMensajes = [];

    public bool $fidelizacionHistorialModalAbierto = false;

    public bool $fidelizacionNuevoModalAbierto = false;

    public array $fidelizacionForm = [
        'prioridad' => 'baja',
        'mensaje' => '',
    ];

    protected AsistenciaService $asistenciaService;

    protected ClienteService $clienteService;

    protected ClientEnrollmentService $clientEnrollmentService;

    protected ClienteMatriculaService $matriculaService;

    protected ClientWellnessService $clientWellnessService;

    protected EnrollmentInstallmentService $enrollmentInstallmentService;

    protected ClienteProfileContextService $profileContextService;

    public function boot(
        AsistenciaService $asistenciaService,
        ClienteService $clienteService,
        ClientEnrollmentService $clientEnrollmentService,
        ClienteMatriculaService $matriculaService,
        ClientWellnessService $clientWellnessService,
        EnrollmentInstallmentService $enrollmentInstallmentService,
        ClienteProfileContextService $profileContextService
    ): void {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clientEnrollmentService = $clientEnrollmentService;
        $this->matriculaService = $matriculaService;
        $this->clientWellnessService = $clientWellnessService;
        $this->enrollmentInstallmentService = $enrollmentInstallmentService;
        $this->profileContextService = $profileContextService;
    }

    public function mount(?Cliente $cliente = null): void
    {
        $this->authorize('cliente.ver');
        $this->matriculaFormSinPagoInicialEnAlta = true;
        $this->clientes = collect([]);
        $this->resetPerfilData();
        $this->loadResponsables();
        $this->matriculaForm['asesor_id'] = auth()->id();
        $this->matriculaForm['fecha_matricula'] = now()->format('Y-m-d');

        if ($cliente?->exists) {
            $this->selectCliente($cliente->id);

            return;
        }

        $clienteId = request()->integer('cliente');
        if ($clienteId > 0) {
            $this->selectCliente($clienteId);
        }
    }

    protected function matriculaTabIsMembresias(): bool
    {
        return $this->tabActiva === 'membresias';
    }

    protected function afterClienteMatriculaMutation(): void
    {
        if ($this->selectedClienteId) {
            $this->refreshSelectedClienteContext($this->selectedClienteId);
        }
    }

    public function updatingClienteSearch($value): void
    {
        $this->codigoSearch = '';
        $this->isSearching = true;

        if ($this->selectedCliente) {
            $nombreCompleto = trim($this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos);
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

    public function updatingCodigoSearch($value): void
    {
        $this->clienteSearch = '';
        $this->isSearching = true;

        if ($this->selectedCliente) {
            $codigoCliente = trim((string) ($this->selectedCliente->codigo ?? ''));
            $valorTrim = trim((string) $value);

            if ($valorTrim !== '' && $valorTrim !== $codigoCliente) {
                $this->clearClienteSelection();
                $this->codigoSearch = $valorTrim;
            }
        }
    }

    public function updatedCodigoSearch(): void
    {
        $this->searchClientesPorCodigo();
    }

    public function updatedResponsableFilter(): void
    {
        if ($this->selectedCliente) {
            $this->clearClienteSelection();
        }

        if (trim($this->codigoSearch) !== '') {
            $this->searchClientesPorCodigo();

            return;
        }

        $this->searchClientes();
    }

    public function searchClientesPorCodigo(): void
    {
        $term = trim($this->codigoSearch);

        if ($term !== '') {
            $this->clientes = $this->clienteService->quickSearchByCodigo($term, 10, $this->responsableFilterId());
        } else {
            $this->clientes = collect([]);
        }

        $this->isSearching = false;
    }

    public function searchClientes(): void
    {
        $searchTerm = trim($this->clienteSearch);

        if (strlen($searchTerm) >= 2) {
            $this->clientes = $this->clienteService->quickSearch($searchTerm, 10, $this->responsableFilterId());
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
        $this->clienteSearch = trim($cliente->nombres.' '.$cliente->apellidos);
        $this->codigoSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->perfilClienteMinimizado = false;
        $this->commercialTabDataLoaded = false;

        $this->refreshSelectedClienteContext($clienteId);
        $this->refreshCrossSucursalAlertForCliente($this->selectedCliente);
        $this->maybeOpenDeudaVencidaModal();
    }

    public function cerrarModalDeudaVencida(): void
    {
        $this->mostrarModalDeudaVencida = false;
        $this->abrirDeudaVencidaAlIniciar = false;
    }

    public function irACuotasPendientesDesdeAviso(): void
    {
        $this->cerrarModalDeudaVencida();
        $this->perfilFinanzasTab = 'cuotas_pendientes';
    }

    protected function maybeOpenDeudaVencidaModal(): void
    {
        $this->deudasVencidasPerfil = $this->resolveDeudasVencidasPerfilItems();

        if ($this->deudasVencidasPerfil === []) {
            $this->mostrarModalDeudaVencida = false;
            $this->abrirDeudaVencidaAlIniciar = false;

            return;
        }

        $this->abrirDeudaVencidaAlIniciar = true;
        $this->mostrarModalDeudaVencida = true;
        $this->dispatchModalDeudaVencida();
    }

    public function abrirModalDeudaVencidaSiPendiente(): void
    {
        if (! $this->abrirDeudaVencidaAlIniciar || $this->deudasVencidasPerfil === []) {
            return;
        }

        $this->mostrarModalDeudaVencida = true;
        $this->dispatchModalDeudaVencida();
    }

    protected function dispatchModalDeudaVencida(): void
    {
        $this->dispatch('modal-show', name: 'deuda-vencida-perfil-modal');

        $this->js(<<<'JS'
            window.dispatchEvent(new CustomEvent('modal-show', {
                detail: { name: 'deuda-vencida-perfil-modal' },
                bubbles: true,
            }));
        JS);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function resolveDeudasVencidasPerfilItems(): array
    {
        if (! $this->membresiaActiva instanceof ClienteMatricula && ! $this->membresiaActiva instanceof ClienteMembresia) {
            return [];
        }

        $tiposMembresia = ['cuota', 'matricula', 'membresia'];

        return collect($this->operacionDiariaDebtSummary['items'] ?? [])
            ->map(function ($item) {
                if (is_array($item)) {
                    return $item;
                }

                return (array) $item;
            })
            ->filter(fn (array $item) => $this->itemDeudaEstaVencida($item))
            ->filter(fn (array $item) => in_array($item['tipo'] ?? '', $tiposMembresia, true))
            ->filter(fn (array $item) => $this->itemDeudaPerteneceAMembresiaActiva($item))
            ->sortBy(fn (array $item) => $this->timestampFechaVencimientoDeuda($item))
            ->values()
            ->all();
    }

    protected function itemDeudaPerteneceAMembresiaActiva(array $item): bool
    {
        if ($this->membresiaActiva instanceof ClienteMatricula) {
            $matriculaId = (int) $this->membresiaActiva->id;

            return match ($item['tipo'] ?? '') {
                'matricula' => (int) ($item['id'] ?? 0) === $matriculaId,
                'cuota' => (int) ($item['cliente_matricula_id'] ?? 0) === $matriculaId,
                default => false,
            };
        }

        if ($this->membresiaActiva instanceof ClienteMembresia) {
            return ($item['tipo'] ?? '') === 'membresia'
                && (int) ($item['id'] ?? 0) === (int) $this->membresiaActiva->id;
        }

        return false;
    }

    protected function itemDeudaEstaVencida(array $item): bool
    {
        return filter_var($item['es_vencida'] ?? false, FILTER_VALIDATE_BOOLEAN);
    }

    protected function timestampFechaVencimientoDeuda(array $item): int
    {
        $fecha = $item['fecha_vencimiento'] ?? null;

        if ($fecha instanceof \Carbon\CarbonInterface) {
            return $fecha->timestamp;
        }

        if (is_string($fecha) && $fecha !== '') {
            return strtotime($fecha) ?: PHP_INT_MAX;
        }

        return PHP_INT_MAX;
    }

    public function clearClienteSelection(): void
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->codigoSearch = '';
        $this->clientes = collect([]);
        $this->isSearching = false;
        $this->perfilClienteMinimizado = false;
        $this->tabActiva = 'membresias';
        $this->resetearSeleccion();
        $this->cobroModalAbierto = false;
        $this->cuotasModalAbierto = false;
        $this->cuotasModalMatriculaId = null;
        $this->crearPlanCuotasModalAbierto = false;
        $this->crearPlanCuotasMatriculaId = null;
        $this->cuotaPagoModalAbierto = false;
        $this->pagoCuotaInstallmentId = null;
        $this->perfilFinanzasTab = 'pagos';
        $this->reservaModalAbierto = false;
        $this->editingRentalId = null;
        $this->trainerModalAbierto = false;
        $this->trainerAsignacionId = null;
        $this->fidelizacionHistorialModalAbierto = false;
        $this->fidelizacionNuevoModalAbierto = false;
        $this->mostrarModalDeudaVencida = false;
        $this->abrirDeudaVencidaAlIniciar = false;
        $this->deudasVencidasPerfil = [];
        $this->resetFidelizacionForm();
    }

    public function togglePerfilClienteMinimizado(): void
    {
        if (! $this->selectedCliente) {
            return;
        }

        $this->perfilClienteMinimizado = ! $this->perfilClienteMinimizado;
    }

    public function setTab(string $tab): void
    {
        $this->tabActiva = in_array($tab, ['membresias', 'matriculas'], true) ? $tab : 'membresias';
        $this->loadCommercialTabData();
    }

    public function loadCommercialTabData(): void
    {
        if (! $this->selectedClienteId || $this->commercialTabDataLoaded) {
            return;
        }

        if (! in_array($this->tabActiva, ['membresias', 'matriculas'], true)) {
            return;
        }

        $commercial = $this->profileContextService->loadCommercialHistory((int) $this->selectedClienteId);
        $this->applyCommercialSummary($commercial);
        $this->commercialTabDataLoaded = true;

        if (! $this->membresiaActiva && $commercial->membresiaActivaFromHistory) {
            $this->membresiaActiva = $commercial->membresiaActivaFromHistory;
        }
    }

    public function registrarIngresoPerfil(): void
    {
        $this->authorize('checking.crear');

        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Selecciona un cliente.');

            return;
        }

        try {
            $validacion = $this->asistenciaService->validarIngreso((int) $this->selectedClienteId);

            if (! ($validacion['valido'] ?? false)) {
                $this->flashToast('error', (string) ($validacion['mensaje'] ?? 'No se pudo registrar el ingreso.'));

                return;
            }

            $this->asistenciaService->registrarIngreso((int) $this->selectedClienteId, (int) auth()->id());
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
            $this->flashToast('success', 'Ingreso registrado exitosamente.');
        } catch (\Exception $e) {
            $this->reportLivewireError($e, 'Error en perfil de cliente.');
        }
    }

    public function registrarSalidaPerfil(): void
    {
        $this->authorize('checking.editar');

        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Selecciona un cliente.');

            return;
        }

        if (! $this->ingresoEnCurso?->id) {
            $this->flashToast('error', 'No hay un ingreso en curso para este cliente.');

            return;
        }

        try {
            $this->asistenciaService->registrarSalida((int) $this->ingresoEnCurso->id);
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
            $this->flashToast('success', 'Salida registrada exitosamente.');
        } catch (\Exception $e) {
            $this->reportLivewireError($e, 'Error en perfil de cliente.');
        }
    }

    protected function cuotaPagoClienteIdScope(): ?int
    {
        return $this->selectedClienteId;
    }

    protected function afterCuotaPagoRegistrado(?Pago $pago = null): void
    {
        $this->closeCuotasModal();
        if ($this->selectedClienteId) {
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
        }
        if ($pago) {
            $this->abrirModalTicketPago($pago->id);
        }
    }

    public function abrirModalTicketPago(int $pagoId): void
    {
        $this->pagoTicketPreviewId = $pagoId;
        $this->mostrarModalTicketPago = true;
    }

    public function cerrarModalTicketPago(): void
    {
        $this->mostrarModalTicketPago = false;
        $this->pagoTicketPreviewId = null;
    }

    public function openReservaModal(?int $rentalId = null): void
    {
        if ($rentalId) {
            $this->authorize('alquiler.editar');
            $rental = Rental::where('cliente_id', $this->selectedClienteId)->find($rentalId);
            if (! $rental) {
                $this->flashToast('error', 'Reserva no encontrada.');

                return;
            }
            $this->editingRentalId = $rental->id;
            $this->reservaForm = [
                'rentable_space_id' => $rental->rentable_space_id,
                'fecha' => $rental->fecha->format('Y-m-d'),
                'hora_inicio' => $rental->hora_inicio?->format('H:i') ?? '',
                'hora_fin' => $rental->hora_fin?->format('H:i') ?? '',
                'precio' => (string) $rental->precio,
                'estado' => $rental->estado,
                'observaciones' => (string) ($rental->observaciones ?? ''),
            ];
        } else {
            $this->authorize('alquiler.crear');
            $this->editingRentalId = null;
            $this->reservaForm = [
                'rentable_space_id' => null,
                'fecha' => now()->format('Y-m-d'),
                'hora_inicio' => '',
                'hora_fin' => '',
                'precio' => '',
                'estado' => 'reservado',
                'observaciones' => '',
            ];
        }

        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Selecciona un cliente.');

            return;
        }

        $this->reservaModalAbierto = true;
    }

    public function closeReservaModal(): void
    {
        $this->reservaModalAbierto = false;
        $this->editingRentalId = null;
    }

    public function guardarReserva(): void
    {
        if (! $this->selectedClienteId) {
            return;
        }

        $this->validate([
            'reservaForm.rentable_space_id' => 'required|exists:rentable_spaces,id',
            'reservaForm.fecha' => 'required|date',
            'reservaForm.hora_inicio' => 'required|string',
            'reservaForm.hora_fin' => 'required|string',
            'reservaForm.precio' => 'required|numeric|min:0',
            'reservaForm.estado' => 'required|in:reservado,confirmado,pagado,cancelado,finalizado',
        ], [], [
            'reservaForm.rentable_space_id' => 'espacio',
        ]);

        try {
            $this->clientWellnessService->assertReservationSlotAvailable(
                (int) $this->reservaForm['rentable_space_id'],
                $this->reservaForm['fecha'],
                $this->reservaForm['hora_inicio'],
                $this->reservaForm['hora_fin'],
                $this->editingRentalId
            );
        } catch (\InvalidArgumentException $e) {
            $this->reportLivewireError($e, 'Error en perfil de cliente.');

            return;
        }

        try {
            if ($this->editingRentalId) {
                $this->authorize('alquiler.editar');
                $rental = Rental::findOrFail($this->editingRentalId);
                $this->clientWellnessService->updateClienteReservation($rental, (int) $this->selectedClienteId, [
                    'rentable_space_id' => (int) $this->reservaForm['rentable_space_id'],
                    'fecha' => $this->reservaForm['fecha'],
                    'hora_inicio' => $this->reservaForm['hora_inicio'],
                    'hora_fin' => $this->reservaForm['hora_fin'],
                    'precio' => $this->reservaForm['precio'],
                    'estado' => $this->reservaForm['estado'],
                    'observaciones' => $this->reservaForm['observaciones'] ?: null,
                ]);
                $this->flashToast('success', 'Reserva actualizada.');
            } else {
                $this->authorize('alquiler.crear');
                $this->clientWellnessService->createReservation((int) $this->selectedClienteId, [
                    'rentable_space_id' => (int) $this->reservaForm['rentable_space_id'],
                    'fecha' => $this->reservaForm['fecha'],
                    'hora_inicio' => $this->reservaForm['hora_inicio'],
                    'hora_fin' => $this->reservaForm['hora_fin'],
                    'precio' => $this->reservaForm['precio'],
                    'estado' => $this->reservaForm['estado'],
                    'observaciones' => $this->reservaForm['observaciones'] ?: null,
                ], (int) auth()->id());
                $this->flashToast('success', 'Reserva creada.');
            }

            $this->closeReservaModal();
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
        } catch (\Exception $e) {
            $this->reportLivewireError($e, 'Error en perfil de cliente.');
        }
    }

    public function openTrainerModal(): void
    {
        $this->authorize('cliente.editar');

        if (! $this->selectedCliente) {
            $this->flashToast('error', __('Selecciona un cliente.'));

            return;
        }

        $this->trainerAsignacionId = $this->selectedCliente->trainer_user_id
            ? (int) $this->selectedCliente->trainer_user_id
            : null;
        $this->trainerModalAbierto = true;
    }

    public function closeTrainerModal(): void
    {
        $this->trainerModalAbierto = false;
        $this->trainerAsignacionId = null;
    }

    public function guardarTrainer(): void
    {
        $this->authorize('cliente.editar');

        if (! $this->selectedClienteId) {
            $this->flashToast('error', __('Selecciona un cliente.'));

            return;
        }

        $this->validate([
            'trainerAsignacionId' => ['nullable', 'exists:users,id'],
        ], [], [
            'trainerAsignacionId' => __('trainer'),
        ]);

        $cliente = Cliente::query()->findOrFail((int) $this->selectedClienteId);
        $trainerId = $this->trainerAsignacionId ? (int) $this->trainerAsignacionId : null;

        if ($trainerId) {
            $trainerExists = User::role('trainer')->whereKey($trainerId)->exists();
            if (! $trainerExists) {
                $this->flashToast('error', __('Seleccione un usuario con rol trainer.'));

                return;
            }
        }

        $cliente->forceFill(['trainer_user_id' => $trainerId])->save();

        $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
        $this->closeTrainerModal();
        $this->flashToast('success', $trainerId ? __('Trainer asignado correctamente.') : __('Trainer removido correctamente.'));
    }

    public function activateClienteEstado(): void
    {
        $this->authorize('biotime.editar');

        if (! $this->selectedCliente) {
            return;
        }

        try {
            $result = app(\App\Services\BioTime\BioTimeClienteEstadoService::class)
                ->activateEstadoCliente($this->selectedCliente);
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
            $message = $result['biotime_command'] !== null
                ? 'Cliente activado; acceso BioTime encolado.'
                : 'Cliente activado correctamente.';
            $this->flashToast('success', $message);
        } catch (\InvalidArgumentException $e) {
            $this->flashToast('error', $e->getMessage());
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function deactivateClienteEstado(): void
    {
        $this->authorize('biotime.editar');

        if (! $this->selectedCliente) {
            return;
        }

        try {
            $result = app(\App\Services\BioTime\BioTimeClienteEstadoService::class)
                ->deactivateEstadoCliente($this->selectedCliente);
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
            $message = $result['biotime_command'] !== null
                ? 'Cliente desactivado; acceso BioTime encolado.'
                : 'Cliente desactivado correctamente.';
            $this->flashToast('success', $message);
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function resetearAccesoApp(): void
    {
        $this->authorize('cliente.editar');

        if (! $this->selectedCliente) {
            return;
        }

        $reseteado = app(\App\Services\ClienteAppAuthService::class)
            ->resetearAcceso($this->selectedCliente);

        if (! $reseteado) {
            $this->flashToast('error', 'El cliente no tiene cuenta de la app.');

            return;
        }

        $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
        $this->flashToast('success', 'Acceso de la app restablecido. El cliente puede volver a activar su cuenta.');
    }

    protected function refreshSelectedClienteContext(int $clienteId): void
    {
        $this->profileContextService->clearCache($clienteId);

        $sections = ['operations', 'wellness', 'crm', 'fidelity'];
        if ($this->commercialTabDataLoaded) {
            $sections[] = 'commercial';
        }

        $context = $this->profileContextService->build($clienteId, $sections);
        $this->applyProfileContext($context);

        if (! $this->commercialTabDataLoaded && in_array($this->tabActiva, ['membresias', 'matriculas'], true)) {
            $this->loadCommercialTabData();
        }
    }

    protected function applyProfileContext(ClienteProfileContext $context): void
    {
        $this->selectedCliente = $context->cliente;
        $ops = $context->operations;

        $this->membresiaActiva = $ops->membresiaActiva;
        $this->operacionDiariaDebtSummary = $ops->operacionDiariaDebtSummary;
        $this->saldoPendiente = $ops->saldoPendiente;
        $this->deudaProductoPendiente = $ops->deudaProductoPendiente;
        $this->deudaMembresiaPendiente = $ops->deudaMembresiaPendiente;
        $this->asistenciasRecientes = $ops->asistenciasRecientes;
        $this->estadisticasAsistencia = $ops->estadisticasAsistencia;
        $this->validacionAcceso = $ops->validacionAcceso;
        $this->ingresoEnCurso = $ops->ingresoEnCurso;
        $this->pagosRecientes = $ops->pagosRecientes;

        $this->applyCommercialSummary($context->commercial);

        if (! $this->membresiaActiva && $context->commercial->membresiaActivaFromHistory) {
            $this->membresiaActiva = $context->commercial->membresiaActivaFromHistory;
        }

        $this->deudasVencidasPerfil = $this->resolveDeudasVencidasPerfilItems();

        $this->reservasEspacios = $context->wellness->reservasEspacios;
        $this->fidelizacionMensajes = $context->fidelity->mensajes;
        $this->usesLegacyMembresiasHistory = $context->meta->usesLegacyMembresiasHistory;
        $this->crmSummary = [
            'tagsCount' => $context->crm->tagsCount,
            'openTasksCount' => $context->crm->openTasksCount,
            'lastActivity' => $context->crm->lastActivity,
            'linkedLead' => $context->crm->linkedLead,
        ];
    }

    protected function applyCommercialSummary(ClienteCommercialSummary $commercial): void
    {
        $this->historialMembresias = $commercial->historialMembresias;
        $this->historialClases = $commercial->historialClases;
        $this->matriculaOpcionesCobro = $commercial->matriculaOpcionesCobro;
        $this->pendienteCuotaPorMatricula = $commercial->pendienteCuotaPorMatricula;
        $this->cuotasCliente = $commercial->cuotasCliente;
        $this->matriculasFinancieras = $commercial->matriculasFinancieras;
        $this->matriculasConCuotas = $commercial->matriculasConCuotas;
        $this->deudaPlanesPendiente = $commercial->deudaPlanesPendiente;
        $this->matriculasSinCronogramaCuotas = $commercial->matriculasSinCronogramaCuotas;
        $this->initializeCuotasMatriculaColapsadas();
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
        $this->ingresoEnCurso = null;
        $this->saldoPendiente = 0.0;
        $this->deudaProductoPendiente = 0.0;
        $this->deudaMembresiaPendiente = 0.0;
        $this->operacionDiariaDebtSummary = [];
        $this->deudasVencidasPerfil = [];
        $this->mostrarModalDeudaVencida = false;
        $this->abrirDeudaVencidaAlIniciar = false;
        $this->historialMembresias = [];
        $this->historialClases = [];
        $this->pagosRecientes = [];
        $this->reservasEspacios = [];
        $this->fidelizacionMensajes = [];
        $this->commercialTabDataLoaded = false;
        $this->usesLegacyMembresiasHistory = false;
        $this->crmSummary = [];
        $this->resetPerfilData();
    }

    public function openFidelizacionHistorialModal(): void
    {
        $this->authorize('cliente.ver');
        if (! $this->selectedClienteId) {
            return;
        }
        $this->fidelizacionHistorialModalAbierto = true;
    }

    public function closeFidelizacionHistorialModal(): void
    {
        $this->fidelizacionHistorialModalAbierto = false;
    }

    public function openFidelizacionNuevoModal(): void
    {
        $this->authorize('cliente.editar');
        if (! $this->selectedClienteId) {
            $this->flashToast('error', __('Selecciona un cliente.'));

            return;
        }
        $this->resetFidelizacionForm();
        $this->fidelizacionNuevoModalAbierto = true;
    }

    public function closeFidelizacionNuevoModal(): void
    {
        $this->fidelizacionNuevoModalAbierto = false;
        $this->resetFidelizacionForm();
    }

    public function guardarFidelizacionMensaje(): void
    {
        $this->authorize('cliente.editar');
        if (! $this->selectedClienteId) {
            $this->flashToast('error', __('Selecciona un cliente.'));

            return;
        }

        $prioridades = array_keys(ClienteFidelizacionMensaje::PRIORIDADES);
        $this->validate([
            'fidelizacionForm.prioridad' => ['required', 'string', 'in:'.implode(',', $prioridades)],
            'fidelizacionForm.mensaje' => ['required', 'string', 'max:5000'],
        ], [], [
            'fidelizacionForm.prioridad' => __('prioridad'),
            'fidelizacionForm.mensaje' => __('mensaje'),
        ]);

        ClienteFidelizacionMensaje::query()->create([
            'cliente_id' => $this->selectedClienteId,
            'user_id' => (int) auth()->id(),
            'prioridad' => $this->fidelizacionForm['prioridad'],
            'mensaje' => $this->fidelizacionForm['mensaje'],
        ]);

        $this->flashToast('success', __('Mensaje de fidelización registrado.'));
        $this->closeFidelizacionNuevoModal();
        $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
    }

    protected function resetFidelizacionForm(): void
    {
        $this->fidelizacionForm = [
            'prioridad' => 'baja',
            'mensaje' => '',
        ];
        $this->resetValidation();
    }

    public function save(): void
    {
        $this->saveMatricula();
    }

    public function closeModal(): void
    {
        $this->closeMatriculaModal();
    }

    public function delete(): void
    {
        $this->deleteMatricula();
    }

    /** @see \App\Livewire\ClienteMatriculas\ClienteMatriculaLive nombres legacy para el partial de modales */
    public function openCreateModal(): void
    {
        $this->openMatriculaCreateModal();
    }

    public function openEditModal($id): void
    {
        $this->openMatriculaEditModal((int) $id);
    }

    public function openDeleteModal($id): void
    {
        $this->openMatriculaDeleteModal((int) $id);
    }

    public function render()
    {
        if ($this->cuotasModalAbierto && $this->cuotasModalMatriculaId) {
            $this->cuotasModalInstallments = $this->enrollmentInstallmentService
                ->installmentsForMatricula($this->cuotasModalMatriculaId);
        } else {
            $this->cuotasModalInstallments = collect([]);
        }

        if ($this->cobroModalAbierto && $this->cobroForm['cliente_matricula_id']) {
            $this->matriculaCobroSeleccionada = ClienteMatricula::query()
                ->with(['membresia', 'clase'])
                ->find((int) $this->cobroForm['cliente_matricula_id']);
        } else {
            $this->matriculaCobroSeleccionada = null;
        }

        if ($this->cobroModalAbierto || $this->cuotaPagoModalAbierto) {
            $this->ensurePaymentMethodsLoaded();
        }

        if ($this->trainerModalAbierto) {
            $this->ensureTrainersLoaded();
        }

        if ($this->reservaModalAbierto) {
            $this->ensureRentableSpacesLoaded();
        }

        if ($this->matriculaModalState['create']) {
            if ($this->matriculaForm['tipo'] === 'membresia') {
                $this->ensureMembresiasActivasLoaded();
            } else {
                $this->ensureClasesActivasLoaded();
            }
        }

        return view('livewire.clientes.cliente-perfil-live', [
            'matriculaOpcionesCobro' => $this->matriculaOpcionesCobro,
            'pendienteCuotaPorMatricula' => $this->pendienteCuotaPorMatricula,
            'cuotasCliente' => $this->cuotasCliente,
            'matriculasFinancieras' => $this->matriculasFinancieras,
            'matriculasConCuotas' => $this->matriculasConCuotas,
            'deudaPlanesPendiente' => $this->deudaPlanesPendiente,
            'paymentMethods' => $this->paymentMethods,
            'matriculasSinCronogramaCuotas' => $this->matriculasSinCronogramaCuotas,
            'cuotasModalInstallments' => $this->cuotasModalInstallments,
            'matriculaCobroSeleccionada' => $this->matriculaCobroSeleccionada,
            'rentableSpaces' => $this->rentableSpaces,
            'trainers' => $this->trainers,
            'responsables' => $this->responsables,
            'membresiasActivas' => $this->membresiasActivas,
            'clasesActivas' => $this->clasesActivas,
            'biotimeSnapshot' => $this->selectedCliente
                ? app(\App\Services\BioTime\BioTimeClienteEstadoService::class)
                    ->profileBioTimeSnapshot($this->selectedCliente)
                : null,
        ]);
    }

    protected function resetPerfilData(): void
    {
        $this->resetCommercialTabData();
        $this->cuotasModalInstallments = collect([]);
        $this->paymentMethods = collect([]);
        $this->rentableSpaces = collect([]);
        $this->trainers = collect([]);
        $this->responsables = $this->responsables ?: collect([]);
        $this->membresiasActivas = collect([]);
        $this->clasesActivas = collect([]);
    }

    protected function loadResponsables(): void
    {
        $this->responsables = User::orderBy('name')->get(['id', 'name']);
    }

    protected function ensurePaymentMethodsLoaded(): void
    {
        if ($this->paymentMethods && $this->paymentMethods->isNotEmpty()) {
            return;
        }

        $this->paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();
    }

    protected function ensureRentableSpacesLoaded(): void
    {
        if ($this->rentableSpaces && $this->rentableSpaces->isNotEmpty()) {
            return;
        }

        $this->rentableSpaces = RentableSpace::orderBy('nombre')->get();
    }

    protected function ensureTrainersLoaded(): void
    {
        if ($this->trainers && $this->trainers->isNotEmpty()) {
            return;
        }

        $this->trainers = User::role('trainer')->orderBy('name')->get(['id', 'name']);
    }

    protected function ensureMembresiasActivasLoaded(): void
    {
        if ($this->membresiasActivas && $this->membresiasActivas->isNotEmpty()) {
            return;
        }

        $this->membresiasActivas = $this->matriculaService->getMembresiasActivas();
    }

    protected function ensureClasesActivasLoaded(): void
    {
        if ($this->clasesActivas && $this->clasesActivas->isNotEmpty()) {
            return;
        }

        $this->clasesActivas = $this->matriculaService->getClasesActivas();
    }

    protected function responsableFilterId(): ?int
    {
        $responsableId = (int) $this->responsableFilter;

        return $responsableId > 0 ? $responsableId : null;
    }
}
