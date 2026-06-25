<?php

namespace App\Livewire\Enrollments\Installments;

use App\Livewire\Concerns\FlashesToast;
use App\Livewire\Concerns\ManagesCuotaPagoModal;
use App\Models\Core\Cliente;
use App\Models\Core\EnrollmentInstallment;
use App\Services\EnrollmentInstallmentService;
use Illuminate\Http\Request;
use Livewire\Component;

class Schedule extends Component
{
    use FlashesToast;
    use ManagesCuotaPagoModal;

    public Cliente $cliente;

    public ?int $highlightMatriculaId = null;

    /** @var array<int, string|float> Borradores de monto por id de cuota (solo pendiente, editable). */
    public array $cuotaMontos = [];

    public function mount(Cliente $cliente, Request $request): void
    {
        $this->authorize('matricula_cliente.ver');
        $this->cliente = $cliente->load([
            'enrollmentInstallmentPlan.installments.clienteMatricula.membresia',
            'enrollmentInstallmentPlan.installments.clienteMatricula.clase',
        ]);
        $m = $request->query('matricula');
        $this->highlightMatriculaId = $m !== null && $m !== '' ? (int) $m : null;
        $this->loadCuotaMontosDrafts();
    }

    protected function cuotaPagoClienteIdScope(): ?int
    {
        return (int) $this->cliente->id;
    }

    protected function afterCuotaPagoRegistrado(?\App\Models\Core\Pago $pago = null): void
    {
        $this->cliente->refresh();
        $this->cliente->load([
            'enrollmentInstallmentPlan.installments.clienteMatricula.membresia',
            'enrollmentInstallmentPlan.installments.clienteMatricula.clase',
        ]);
        $this->loadCuotaMontosDrafts();
    }

    public function loadCuotaMontosDrafts(): void
    {
        $this->cuotaMontos = [];
        $plan = $this->cliente->enrollmentInstallmentPlan;
        if (! $plan) {
            return;
        }
        foreach ($plan->installments()->where('estado', 'pendiente')->orderBy('fecha_vencimiento')->orderBy('id')->get() as $ins) {
            $this->cuotaMontos[$ins->id] = (string) $ins->monto;
        }
    }

    public function actualizarMontoCuota(int $installmentId): void
    {
        $this->authorize('matricula_cliente.editar');
        $raw = $this->cuotaMontos[$installmentId] ?? null;
        if ($raw === null || $raw === '') {
            $this->loadCuotaMontosDrafts();

            return;
        }

        $nuevo = round((float) str_replace(',', '.', (string) $raw), 2);

        $ins = EnrollmentInstallment::query()->with('plan')->find($installmentId);
        if (! $ins || ! $ins->plan || (int) $ins->plan->cliente_id !== (int) $this->cliente->id) {
            $this->flashToast('error', __('Cuota no válida para este cliente.'));
            $this->loadCuotaMontosDrafts();

            return;
        }

        try {
            app(EnrollmentInstallmentService::class)->updatePendienteMontoRedistributeTail($ins, $nuevo);
            $this->flashToast('success', __('Montos actualizados; las cuotas pendientes posteriores se recalcularon.'));
            $this->cliente->refresh();
            $this->cliente->load([
                'enrollmentInstallmentPlan.installments.clienteMatricula.membresia',
                'enrollmentInstallmentPlan.installments.clienteMatricula.clase',
            ]);
            $this->loadCuotaMontosDrafts();
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
            $this->loadCuotaMontosDrafts();
        }
    }

    public function render()
    {
        $plan = $this->cliente->enrollmentInstallmentPlan;
        $installments = $plan
            ? $plan->installments()
                ->with(['clienteMatricula.membresia', 'clienteMatricula.clase', 'pagos', 'pago'])
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->get()
            : collect();

        $paymentMethods = $this->cuotaPagoModalAbierto
            ? $this->paymentMethodsForCuotaModal()
            : collect();

        return view('livewire.enrollments.installments.schedule', [
            'plan' => $plan,
            'installments' => $installments,
            'paymentMethods' => $paymentMethods,
        ])->layout('layouts.app', ['title' => 'Cronograma de cuotas']);
    }
}
