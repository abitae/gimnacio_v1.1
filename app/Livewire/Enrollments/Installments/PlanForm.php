<?php

namespace App\Livewire\Enrollments\Installments;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Services\EnrollmentInstallmentService;
use Illuminate\Http\Request;
use Livewire\Component;

class PlanForm extends Component
{
    use FlashesToast;

    public Cliente $cliente;

    public ClienteMatricula $clienteMatricula;

    public array $form = [
        'monto_total' => '',
        'cuota_inicial_monto' => '0',
        'numero_cuotas' => '',
        'frecuencia' => 'quincenal',
        'fecha_inicio' => '',
        'observaciones' => '',
        'schedule' => [],
    ];

    public function mount(Cliente $cliente, Request $request): void
    {
        $this->authorize('matricula_cliente.crear');
        $mid = (int) ($request->query('matricula') ?? 0);
        $this->cliente = $cliente;
        $this->clienteMatricula = ClienteMatricula::query()
            ->where('cliente_id', $cliente->id)
            ->findOrFail($mid);

        if ($this->clienteMatricula->enrollmentInstallments()->exists()) {
            $this->flashToast('info', 'Esta matrícula ya tiene cuotas en el plan del cliente.');
            $this->redirectRoute('clientes.cuotas', [
                'cliente' => $this->cliente->id,
                'matricula' => $this->clienteMatricula->id,
            ], navigate: true);

            return;
        }

        $this->form['monto_total'] = (string) $this->clienteMatricula->precio_final;
        $this->form['cuota_inicial_monto'] = (string) ($this->clienteMatricula->cuota_inicial_monto ?? 0);
        $this->form['fecha_inicio'] = $this->clienteMatricula->fecha_matricula?->format('Y-m-d') ?? now()->format('Y-m-d');
        $this->generarVistaPrevia(app(EnrollmentInstallmentService::class));
    }

    public function updatedFormMontoTotal(): void
    {
        $this->generarVistaPrevia(app(EnrollmentInstallmentService::class));
    }

    public function updatedFormCuotaInicialMonto(): void
    {
        $this->generarVistaPrevia(app(EnrollmentInstallmentService::class));
    }

    public function updatedFormNumeroCuotas(): void
    {
        $this->generarVistaPrevia(app(EnrollmentInstallmentService::class));
    }

    public function updatedFormFrecuencia(): void
    {
        $this->generarVistaPrevia(app(EnrollmentInstallmentService::class));
    }

    public function updatedFormFechaInicio(): void
    {
        $this->generarVistaPrevia(app(EnrollmentInstallmentService::class));
    }

    public function agregarCuotaManual(): void
    {
        $this->form['schedule'][] = [
            'fecha_vencimiento' => now()->format('Y-m-d'),
            'monto' => 0,
        ];
    }

    public function quitarCuotaManual(int $index): void
    {
        unset($this->form['schedule'][$index]);
        $this->form['schedule'] = array_values($this->form['schedule']);
    }

    public function save(EnrollmentInstallmentService $service): void
    {
        $this->validate([
            'form.monto_total' => 'required|numeric|min:0.01',
            'form.cuota_inicial_monto' => 'required|numeric|min:0',
            'form.numero_cuotas' => 'required|integer|min:2|max:60',
            'form.frecuencia' => 'required|in:quincenal,mensual',
            'form.fecha_inicio' => 'required|date',
            'form.schedule' => 'required|array|min:1',
            'form.schedule.*.fecha_vencimiento' => 'required|date',
            'form.schedule.*.monto' => 'required|numeric|min:0.01',
        ]);

        if ((float) $this->form['cuota_inicial_monto'] > (float) $this->form['monto_total']) {
            $this->flashToast('error', 'La cuota inicial no puede ser mayor al monto total.');

            return;
        }

        try {
            $service->createPlan($this->clienteMatricula, $this->form);
            $this->flashToast('success', 'Cuotas registradas en el plan del cliente.');
            $this->redirectRoute('clientes.cuotas', [
                'cliente' => $this->cliente->id,
                'matricula' => $this->clienteMatricula->id,
            ], navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.enrollments.installments.plan-form')
            ->layout('layouts.app', ['title' => 'Crear plan de cuotas']);
    }

    private function generarVistaPrevia(EnrollmentInstallmentService $service): void
    {
        if ((int) ($this->form['numero_cuotas'] ?? 0) < 1 || blank($this->form['fecha_inicio'] ?? null)) {
            return;
        }

        try {
            $this->form['schedule'] = $service->previewSchedule($this->form);
        } catch (\Throwable) {
            // La validación se mostrará al guardar; aquí solo evitamos romper el formulario.
        }
    }
}
