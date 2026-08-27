<?php

namespace App\Livewire\Clientes\Concerns;

use App\Models\Core\ClienteMatricula;
use App\Models\Core\PaymentMethod;

/**
 * Tab comercial transaccional: cobros de matrícula, cuotas y planes.
 *
 * @property int|null $selectedClienteId
 */
trait ManagesClienteCommercialTab
{
    public $matriculaOpcionesCobro;

    public array $pendienteCuotaPorMatricula = [];

    public $cuotasCliente;

    public $matriculasFinancieras;

    public $matriculasConCuotas;

    public float $deudaPlanesPendiente = 0.0;

    public $matriculasSinCronogramaCuotas;

    public $matriculaCobroSeleccionada = null;

    public bool $cobroModalAbierto = false;

    public array $cobroForm = [
        'cliente_matricula_id' => null,
        'monto_pago' => '',
        'fecha_pago' => '',
        'pagos' => [],
    ];

    public bool $cuotasModalAbierto = false;

    public ?int $cuotasModalMatriculaId = null;

    public bool $crearPlanCuotasModalAbierto = false;

    public ?int $crearPlanCuotasMatriculaId = null;

    /** @var list<int> IDs de matrículas con la tabla de cuotas colapsada */
    public array $cuotasMatriculaColapsadas = [];

    public array $crearPlanCuotasForm = [
        'monto_total' => '',
        'numero_cuotas' => '',
        'frecuencia' => 'mensual',
        'fecha_inicio' => '',
        'observaciones' => '',
    ];

    abstract protected function refreshSelectedClienteContext(int $clienteId): void;

    public function openCobroMatriculaModal(?int $clienteMatriculaId = null): void
    {
        $this->authorize('matricula_cliente.editar');
        if (! $this->selectedClienteId) {
            $this->flashToast('error', 'Selecciona un cliente.');

            return;
        }

        if ($clienteMatriculaId === null) {
            $clienteMatriculaId = $this->resolvePrimerMatriculaCobrableId((int) $this->selectedClienteId);
            if (! $clienteMatriculaId) {
                $this->flashToast('info', 'No hay matrículas pendientes de cobro para este cliente.');

                return;
            }
        }

        if ($clienteMatriculaId) {
            $m = $this->matriculaService->find($clienteMatriculaId);
            if (! $m || (int) $m->cliente_id !== (int) $this->selectedClienteId) {
                $this->flashToast('error', 'Matrícula no válida para este cliente.');

                return;
            }

            if ($m->usaPlanCuotas()) {
                $inst = $this->enrollmentInstallmentService->firstPayableInstallmentForMatricula($clienteMatriculaId);
                if ($inst) {
                    $this->openRegistrarPagoCuota($inst->id);

                    return;
                }
                $this->flashToast('info', 'No hay cuotas pendientes para cobrar en esta matrícula.');

                return;
            }

            $saldo = $this->matriculaService->obtenerSaldoPendiente($clienteMatriculaId);
            if ($saldo <= 0) {
                $this->flashToast('info', 'Esta matrícula ya no tiene saldo pendiente.');

                return;
            }
        }

        $this->cobroForm = [
            'cliente_matricula_id' => $clienteMatriculaId,
            'monto_pago' => '',
            'fecha_pago' => now()->format('Y-m-d'),
            'pagos' => [],
        ];

        if ($clienteMatriculaId) {
            $m = $this->matriculaService->find($clienteMatriculaId);
            if ($m) {
                $saldo = $this->matriculaService->obtenerSaldoPendiente($clienteMatriculaId);
                $this->cobroForm['monto_pago'] = $saldo > 0 ? (string) $saldo : '';
                $this->cobroForm['pagos'] = [$this->nuevaLineaCobroMatricula(
                    $this->cobroForm['monto_pago'],
                    $this->metodoEfectivoIdCobroMatricula(),
                )];
            }
        }

        $this->cobroModalAbierto = true;
    }

    public function openPrimeraCuotasConPlan(): void
    {
        $this->authorize('matricula_cliente.ver');
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
        $this->authorize('matricula_cliente.crear');
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
        $this->authorize('matricula_cliente.crear');
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
        $this->authorize('matricula_cliente.ver');
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

    public function closeCobroMatriculaModal(): void
    {
        $this->cobroModalAbierto = false;
    }

    public function agregarFormaCobroMatricula(): void
    {
        if (count($this->cobroForm['pagos'] ?? []) < 2) {
            $this->cobroForm['pagos'][] = $this->nuevaLineaCobroMatricula();
        }
    }

    public function quitarFormaCobroMatricula(int $index): void
    {
        if ($index === 0 || count($this->cobroForm['pagos'] ?? []) <= 1) {
            return;
        }

        unset($this->cobroForm['pagos'][$index]);
        $this->cobroForm['pagos'] = array_values($this->cobroForm['pagos']);
    }

    public function updatedCobroFormMontoPago($value): void
    {
        if (count($this->cobroForm['pagos'] ?? []) === 1) {
            $this->cobroForm['pagos'][0]['monto'] = $value;
        }
    }

    public function getCobroMatriculaTotalAsignadoProperty(): float
    {
        return round((float) collect($this->cobroForm['pagos'] ?? [])->sum(fn ($linea) => (float) ($linea['monto'] ?? 0)), 2);
    }

    public function getCobroMatriculaDiferenciaProperty(): float
    {
        return round((float) ($this->cobroForm['monto_pago'] ?? 0) - $this->cobroMatriculaTotalAsignado, 2);
    }

    public function guardarCobroMatricula(): void
    {
        $this->authorize('matricula_cliente.editar');
        $this->validate([
            'cobroForm.cliente_matricula_id' => 'required|exists:cliente_matriculas,id',
            'cobroForm.monto_pago' => 'required|numeric|min:0.01',
            'cobroForm.fecha_pago' => 'required|date',
            'cobroForm.pagos' => 'required|array|min:1|max:2',
            'cobroForm.pagos.*.payment_method_id' => 'required|exists:payment_methods,id',
            'cobroForm.pagos.*.monto' => 'required|numeric|min:0.01',
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

            $pago = $this->matriculaService->procesarPago($mid, [
                'monto_pago' => (float) $this->cobroForm['monto_pago'],
                'fecha_pago' => $this->cobroForm['fecha_pago'],
                'pagos' => $this->cobroForm['pagos'],
            ]);

            $this->flashToast('success', 'Cobro registrado correctamente.');
            $this->closeCobroMatriculaModal();
            $this->refreshSelectedClienteContext((int) $this->selectedClienteId);
            $this->abrirModalTicketPago($pago->id);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function resolvePrimerMatriculaCobrableId(int $clienteId): ?int
    {
        $matricula = ClienteMatricula::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', '!=', 'cancelada')
            ->with(['pagos'])
            ->orderByDesc('fecha_inicio')
            ->get()
            ->first(function (ClienteMatricula $matricula) {
                return ! $matricula->usaPlanCuotas() && $matricula->saldo_pendiente_actual > 0;
            });

        return $matricula?->id;
    }

    protected function metodoEfectivoIdCobroMatricula(): ?int
    {
        return PaymentMethod::activos()
            ->whereRaw('LOWER(nombre) = ?', ['efectivo'])
            ->value('id') ?? PaymentMethod::activos()->orderBy('nombre')->value('id');
    }

    protected function nuevaLineaCobroMatricula(string|float $monto = '', ?int $paymentMethodId = null): array
    {
        return [
            'payment_method_id' => $paymentMethodId,
            'monto' => $monto,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
    }

    protected function resetCommercialTabData(): void
    {
        $this->matriculaOpcionesCobro = collect([]);
        $this->pendienteCuotaPorMatricula = [];
        $this->cuotasCliente = collect([]);
        $this->matriculasFinancieras = collect([]);
        $this->matriculasConCuotas = collect([]);
        $this->deudaPlanesPendiente = 0.0;
        $this->matriculasSinCronogramaCuotas = collect([]);
        $this->matriculaCobroSeleccionada = null;
        $this->cuotasMatriculaColapsadas = [];
    }

    public function toggleCuotasMatricula(int $matriculaId): void
    {
        if (in_array($matriculaId, $this->cuotasMatriculaColapsadas, true)) {
            $this->cuotasMatriculaColapsadas = array_values(array_filter(
                $this->cuotasMatriculaColapsadas,
                fn (int $id) => $id !== $matriculaId
            ));
        } else {
            $this->cuotasMatriculaColapsadas[] = $matriculaId;
        }
    }

    public function isCuotasMatriculaColapsada(int $matriculaId): bool
    {
        return in_array($matriculaId, $this->cuotasMatriculaColapsadas, true);
    }

    protected function initializeCuotasMatriculaColapsadas(): void
    {
        $colapsadas = [];

        foreach ($this->matriculasConCuotas ?? [] as $matriculaCuotas) {
            $estado = strtolower((string) ($matriculaCuotas['estado_matricula'] ?? ''));
            if (! in_array($estado, ['activa', 'activo'], true)) {
                $colapsadas[] = (int) $matriculaCuotas['id'];
            }
        }

        $this->cuotasMatriculaColapsadas = $colapsadas;
    }
}
