<?php

namespace App\Livewire\Clientes;

use App\Livewire\Clientes\Concerns\ManagesClienteCrudAndPhoto;
use App\Livewire\Concerns\FlashesToast;
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
use App\Services\AsistenciaService;
use App\Services\ClienteMatriculaService;
use App\Services\ClientEnrollmentService;
use App\Services\ClienteService;
use App\Services\ClientWellnessService;
use App\Services\EnrollmentInstallmentService;
use Illuminate\Support\Collection;
use Livewire\Component;

class ClientePerfilLive extends Component
{
    use FlashesToast;
    use ManagesClienteCrudAndPhoto;
    use ManagesClienteMatriculaForm;
    use ManagesCuotaPagoModal;

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

    /** Suma de saldos pendientes de ventas a crédito / producto (`client_debts`). */
    public float $deudaProductoPendiente = 0.0;

    public array $historialMembresias = [];

    public array $historialClases = [];

    public array $pagosRecientes = [];

    /** @var array<int, \App\Models\Core\Rental> */
    public array $reservasEspacios = [];

    public bool $cobroModalAbierto = false;

    public array $cobroForm = [
        'cliente_matricula_id' => null,
        'monto_pago' => '',
        'fecha_pago' => '',
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
    ];

    public bool $cuotasModalAbierto = false;

    public ?int $cuotasModalMatriculaId = null;

    public bool $crearPlanCuotasModalAbierto = false;

    public ?int $crearPlanCuotasMatriculaId = null;

    public array $crearPlanCuotasForm = [
        'monto_total' => '',
        'numero_cuotas' => '',
        'frecuencia' => 'mensual',
        'fecha_inicio' => '',
        'observaciones' => '',
    ];

    /** @var 'cuotas_pendientes'|'pagos' */
    public string $perfilFinanzasTab = 'cuotas_pendientes';

    public bool $reservaModalAbierto = false;

    public ?int $editingRentalId = null;

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

    public function boot(
        AsistenciaService $asistenciaService,
        ClienteService $clienteService,
        ClientEnrollmentService $clientEnrollmentService,
        ClienteMatriculaService $matriculaService,
        ClientWellnessService $clientWellnessService,
        EnrollmentInstallmentService $enrollmentInstallmentService
    ): void {
        $this->asistenciaService = $asistenciaService;
        $this->clienteService = $clienteService;
        $this->clientEnrollmentService = $clientEnrollmentService;
        $this->matriculaService = $matriculaService;
        $this->clientWellnessService = $clientWellnessService;
        $this->enrollmentInstallmentService = $enrollmentInstallmentService;
    }

    public function mount(?Cliente $cliente = null): void
    {
        $this->authorize('clientes.view');
        $this->matriculaFormSinPagoInicialEnAlta = true;
        $this->clientes = collect([]);
        $this->matriculaForm['asesor_id'] = auth()->id();
        $this->matriculaForm['fecha_matricula'] = now()->format('Y-m-d');

        if ($cliente) {
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
        $this->clienteSearch = trim($cliente->nombres.' '.$cliente->apellidos);
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
        $this->cobroModalAbierto = false;
        $this->cuotasModalAbierto = false;
        $this->cuotasModalMatriculaId = null;
        $this->crearPlanCuotasModalAbierto = false;
        $this->crearPlanCuotasMatriculaId = null;
        $this->cuotaPagoModalAbierto = false;
        $this->pagoCuotaInstallmentId = null;
        $this->perfilFinanzasTab = 'cuotas_pendientes';
        $this->reservaModalAbierto = false;
        $this->editingRentalId = null;
        $this->fidelizacionHistorialModalAbierto = false;
        $this->fidelizacionNuevoModalAbierto = false;
        $this->resetFidelizacionForm();
    }

    public function setTab(string $tab): void
    {
        $this->tabActiva = in_array($tab, ['membresias', 'matriculas'], true) ? $tab : 'membresias';
    }

    public function openCobroMatriculaModal(?int $clienteMatriculaId = null): void
    {
        $this->authorize('cliente-matriculas.update');
        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Selecciona un cliente.');

            return;
        }

        if ($clienteMatriculaId) {
            $m = $this->matriculaService->find($clienteMatriculaId);
            if ($m && (int) $m->cliente_id === (int) $this->selectedClienteId && $m->usaPlanCuotas()) {
                $inst = $this->enrollmentInstallmentService->firstPayableInstallmentForMatricula($clienteMatriculaId);
                if ($inst) {
                    $this->openRegistrarPagoCuota($inst->id);

                    return;
                }
                $this->flashToast('info', 'No hay cuotas pendientes para cobrar en esta matrícula.');

                return;
            }
        }

        $this->cobroForm = [
            'cliente_matricula_id' => $clienteMatriculaId,
            'monto_pago' => '',
            'fecha_pago' => now()->format('Y-m-d'),
            'payment_method_id' => null,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];

        if ($clienteMatriculaId) {
            $m = $this->matriculaService->find($clienteMatriculaId);
            if ($m) {
                $saldo = $this->matriculaService->obtenerSaldoPendiente($clienteMatriculaId);
                $this->cobroForm['monto_pago'] = $saldo > 0 ? (string) $saldo : '';
            }
        }

        $this->cobroModalAbierto = true;
    }

    public function updatedCobroFormClienteMatriculaId($value): void
    {
        if (! $value || ! $this->cobroModalAbierto) {
            return;
        }
        $mid = (int) $value;
        $m = $this->matriculaService->find($mid);
        if ($m && (int) $m->cliente_id === (int) $this->selectedClienteId) {
            $saldo = $this->matriculaService->obtenerSaldoPendiente($mid);
            $this->cobroForm['monto_pago'] = $saldo > 0 ? (string) $saldo : '';
        }
    }

    public function openPrimeraCuotasConPlan(): void
    {
        $this->authorize('cliente-matriculas.view');
        if (! $this->selectedClienteId) {
            return;
        }
        $m = ClienteMatricula::query()
            ->where('cliente_id', $this->selectedClienteId)
            ->where('estado', '!=', 'cancelada')
            ->orderByDesc('fecha_inicio')
            ->get()
            ->first(fn (ClienteMatricula $row) => $row->usaPlanCuotas());
        if (! $m) {
            $this->flashToast('info', __('Este cliente no tiene matrículas con plan de cuotas.'));

            return;
        }
        if (! $m->enrollmentInstallments()->exists()) {
            $this->flashToast('info', __('Esta matrícula aún no tiene cronograma de cuotas. Use «Crear plan de cuotas».'));

            return;
        }
        $this->openCuotasModal($m->id);
    }

    public function openCrearPlanCuotasModal(): void
    {
        $this->authorize('cliente-matriculas.create');
        if (! $this->selectedClienteId) {
            return;
        }

        if ($this->enrollmentInstallmentService->installmentsForCliente((int) $this->selectedClienteId)->isNotEmpty()) {
            $this->flashToast('info', __('Este cliente ya tiene cuotas registradas.'));

            return;
        }

        $candidates = ClienteMatricula::query()
            ->where('cliente_id', $this->selectedClienteId)
            ->where('estado', '!=', 'cancelada')
            ->orderByDesc('fecha_inicio')
            ->get()
            ->filter(fn (ClienteMatricula $row) => $row->usaPlanCuotas() && ! $row->enrollmentInstallments()->exists());

        if ($candidates->isEmpty()) {
            $this->flashToast('info', __('No hay matrículas en cuotas sin cronograma. Cree una matrícula con modalidad cuotas primero.'));

            return;
        }

        $first = $candidates->first();
        $this->crearPlanCuotasMatriculaId = $first->id;
        $this->prefillCrearPlanCuotasForm($first);
        $this->crearPlanCuotasModalAbierto = true;
    }

    public function closeCrearPlanCuotasModal(): void
    {
        $this->crearPlanCuotasModalAbierto = false;
        $this->crearPlanCuotasMatriculaId = null;
    }

    public function updatedCrearPlanCuotasMatriculaId($value): void
    {
        if (! $this->crearPlanCuotasModalAbierto || ! $value || ! $this->selectedClienteId) {
            return;
        }

        $m = ClienteMatricula::query()
            ->where('cliente_id', $this->selectedClienteId)
            ->find((int) $value);

        if ($m) {
            $this->prefillCrearPlanCuotasForm($m);
        }
    }

    public function guardarCrearPlanCuotas(): void
    {
        $this->authorize('cliente-matriculas.create');
        $this->validate([
            'crearPlanCuotasMatriculaId' => 'required|exists:cliente_matriculas,id',
            'crearPlanCuotasForm.monto_total' => 'required|numeric|min:0.01',
            'crearPlanCuotasForm.numero_cuotas' => 'required|integer|min:2|max:60',
            'crearPlanCuotasForm.frecuencia' => 'required|in:semanal,quincenal,mensual,anual,personalizado',
            'crearPlanCuotasForm.fecha_inicio' => 'required|date',
        ], [], [
            'crearPlanCuotasMatriculaId' => 'matrícula',
            'crearPlanCuotasForm.monto_total' => 'monto total',
            'crearPlanCuotasForm.numero_cuotas' => 'número de cuotas',
        ]);

        if (! $this->selectedClienteId) {
            return;
        }

        $mat = ClienteMatricula::query()
            ->where('cliente_id', $this->selectedClienteId)
            ->findOrFail((int) $this->crearPlanCuotasMatriculaId);

        if (! $mat->usaPlanCuotas()) {
            $this->flashToast('error', __('La matrícula seleccionada no está en modalidad cuotas.'));

            return;
        }

        if ($mat->enrollmentInstallments()->exists()) {
            $this->flashToast('error', __('Esta matrícula ya tiene cuotas registradas.'));

            return;
        }

        if ($this->enrollmentInstallmentService->installmentsForCliente((int) $this->selectedClienteId)->isNotEmpty()) {
            $this->flashToast('error', __('El cliente ya tiene cuotas en el plan.'));

            return;
        }

        try {
            $this->enrollmentInstallmentService->createPlan($mat, $this->crearPlanCuotasForm);
            $this->flashToast('success', __('Plan de cuotas creado.'));
            $this->closeCrearPlanCuotasModal();
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
            $this->openCuotasModal($mat->id);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function prefillCrearPlanCuotasForm(?ClienteMatricula $matricula): void
    {
        if (! $matricula) {
            $this->crearPlanCuotasForm = [
                'monto_total' => '',
                'numero_cuotas' => '',
                'frecuencia' => 'mensual',
                'fecha_inicio' => now()->format('Y-m-d'),
                'observaciones' => '',
            ];

            return;
        }

        $this->crearPlanCuotasForm = [
            'monto_total' => (string) $matricula->monto_financiado,
            'numero_cuotas' => '',
            'frecuencia' => 'mensual',
            'fecha_inicio' => $matricula->fecha_matricula?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'observaciones' => '',
        ];
    }

    public function openCuotasModal(int $clienteMatriculaId): void
    {
        $this->authorize('cliente-matriculas.view');
        if (! $this->selectedClienteId) {
            return;
        }
        $m = ClienteMatricula::query()
            ->where('cliente_id', $this->selectedClienteId)
            ->find($clienteMatriculaId);
        if (! $m || ! $m->usaPlanCuotas()) {
            $this->flashToast('error', 'Esta matrícula no tiene cronograma de cuotas.');

            return;
        }
        $this->cuotasModalMatriculaId = $clienteMatriculaId;
        $this->cuotasModalAbierto = true;
    }

    public function closeCuotasModal(): void
    {
        $this->cuotasModalAbierto = false;
        $this->cuotasModalMatriculaId = null;
    }

    protected function cuotaPagoClienteIdScope(): ?int
    {
        return $this->selectedClienteId;
    }

    protected function afterCuotaPagoRegistrado(): void
    {
        $this->closeCuotasModal();
        if ($this->selectedClienteId) {
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
        }
    }

    public function closeCobroMatriculaModal(): void
    {
        $this->cobroModalAbierto = false;
    }

    public function guardarCobroMatricula(): void
    {
        $this->authorize('cliente-matriculas.update');
        $this->validate([
            'cobroForm.cliente_matricula_id' => 'required|exists:cliente_matriculas,id',
            'cobroForm.monto_pago' => 'required|numeric|min:0.01',
            'cobroForm.fecha_pago' => 'required|date',
            'cobroForm.payment_method_id' => 'nullable|exists:payment_methods,id',
        ], [], [
            'cobroForm.cliente_matricula_id' => 'matrícula',
            'cobroForm.monto_pago' => 'monto',
        ]);

        try {
            $mid = (int) $this->cobroForm['cliente_matricula_id'];
            $mat = $this->matriculaService->find($mid);
            if (! $mat || (int) $mat->cliente_id !== (int) $this->selectedClienteId) {
                throw new \InvalidArgumentException('Matrícula no válida para este cliente.');
            }
            if ($mat->usaPlanCuotas()) {
                $this->flashToast('error', 'Esta matrícula se cobra por cuotas. Use «Ver cuotas» o el cobro guiado de cuotas.');

                return;
            }

            $this->matriculaService->procesarPago($mid, [
                'monto_pago' => (float) $this->cobroForm['monto_pago'],
                'fecha_pago' => $this->cobroForm['fecha_pago'],
                'payment_method_id' => $this->cobroForm['payment_method_id'] ?: null,
                'numero_operacion' => $this->cobroForm['numero_operacion'] ?: null,
                'entidad_financiera' => $this->cobroForm['entidad_financiera'] ?: null,
            ]);

            $this->flashToast('success', 'Cobro registrado correctamente.');
            $this->closeCobroMatriculaModal();
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function openReservaModal(?int $rentalId = null): void
    {
        if ($rentalId) {
            $this->authorize('rentals.update');
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
            $this->authorize('rentals.create');
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
            $this->flashToast('error', $e->getMessage());

            return;
        }

        try {
            if ($this->editingRentalId) {
                $this->authorize('rentals.update');
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
                $this->authorize('rentals.create');
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
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function refreshSelectedClienteContext(int $clienteId): void
    {
        $this->selectedCliente = $this->clienteService->find($clienteId);

        $activeEnrollment = $this->clientEnrollmentService->resolveActiveEnrollment($clienteId);
        $this->membresiaActiva = $activeEnrollment['source_model'] ?? null;
        $this->saldoPendiente = (float) ($activeEnrollment['saldo_pendiente'] ?? 0);
        $this->deudaProductoPendiente = (float) $this->selectedCliente->clientDebts()->pendientes()->sum('saldo_pendiente');
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

        $this->reservasEspacios = $this->clientWellnessService
            ->listReservationsUnifiedForCliente($clienteId)
            ->all();

        $this->fidelizacionMensajes = ClienteFidelizacionMensaje::query()
            ->where('cliente_id', $clienteId)
            ->with('autor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
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
        $this->deudaProductoPendiente = 0.0;
        $this->historialMembresias = [];
        $this->historialClases = [];
        $this->pagosRecientes = [];
        $this->reservasEspacios = [];
        $this->fidelizacionMensajes = [];
    }

    public function openFidelizacionHistorialModal(): void
    {
        $this->authorize('clientes.view');
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
        $this->authorize('clientes.update');
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
        $this->authorize('clientes.update');
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
        $matriculaOpcionesCobro = collect([]);
        $pendienteCuotaPorMatricula = [];
        $paymentMethods = collect([]);
        $matriculasSinCronogramaCuotas = collect([]);

        if ($this->selectedClienteId) {
            if ($this->enrollmentInstallmentService->installmentsForCliente((int) $this->selectedClienteId)->isEmpty()) {
                $matriculasSinCronogramaCuotas = ClienteMatricula::query()
                    ->where('cliente_id', $this->selectedClienteId)
                    ->where('estado', '!=', 'cancelada')
                    ->orderByDesc('fecha_inicio')
                    ->get()
                    ->filter(fn (ClienteMatricula $row) => $row->usaPlanCuotas() && ! $row->enrollmentInstallments()->exists())
                    ->values();
            }

            $todasMatriculasCliente = ClienteMatricula::query()
                ->where('cliente_id', $this->selectedClienteId)
                ->where('estado', '!=', 'cancelada')
                ->orderByDesc('fecha_inicio')
                ->get();

            foreach ($todasMatriculasCliente as $row) {
                if ($row->usaPlanCuotas()) {
                    $inst = $this->enrollmentInstallmentService->firstPayableInstallmentForMatricula($row->id);
                    if ($inst) {
                        $pendienteCuotaPorMatricula[$row->id] = $inst;
                    }
                }
            }

            $matriculaOpcionesCobro = $todasMatriculasCliente
                ->filter(fn (ClienteMatricula $row) => ! $row->usaPlanCuotas())
                ->values();

            if ($this->cobroModalAbierto || $this->cuotaPagoModalAbierto) {
                $paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();
            }
        }

        $cuotasModalInstallments = collect([]);
        if ($this->cuotasModalAbierto && $this->cuotasModalMatriculaId) {
            $cuotasModalInstallments = $this->enrollmentInstallmentService
                ->installmentsForMatricula($this->cuotasModalMatriculaId);
        }

        $rentableSpaces = collect([]);
        if ($this->reservaModalAbierto) {
            $rentableSpaces = RentableSpace::orderBy('nombre')->get();
        }

        $membresiasActivas = collect([]);
        $clasesActivas = collect([]);
        if ($this->matriculaModalState['create']) {
            if ($this->matriculaForm['tipo'] === 'membresia') {
                $membresiasActivas = $this->matriculaService->getMembresiasActivas();
            } else {
                $clasesActivas = $this->matriculaService->getClasesActivas();
            }
        }

        return view('livewire.clientes.cliente-perfil-live', [
            'matriculaOpcionesCobro' => $matriculaOpcionesCobro,
            'pendienteCuotaPorMatricula' => $pendienteCuotaPorMatricula,
            'paymentMethods' => $paymentMethods,
            'matriculasSinCronogramaCuotas' => $matriculasSinCronogramaCuotas,
            'cuotasModalInstallments' => $cuotasModalInstallments,
            'rentableSpaces' => $rentableSpaces,
            'membresiasActivas' => $membresiasActivas,
            'clasesActivas' => $clasesActivas,
        ]);
    }
}
