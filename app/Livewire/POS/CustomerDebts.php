<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\ClientDebt;
use App\Models\Core\PaymentMethod;
use App\Services\ClientDebtService;
use Livewire\Component;
use Livewire\WithPagination;

class CustomerDebts extends Component
{
    use FlashesToast;
    use WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public int $perPage = 15;

    public bool $mostrarModalCobro = false;

    public ?int $debtIdSeleccionada = null;

    public array $cobroForm = [
        'monto_pago' => 0.00,
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
    ];

    public bool $mostrarModalTicketPago = false;

    public ?int $pagoIdTicket = null;

    protected $paginationTheme = 'tailwind';

    protected ClientDebtService $clientDebtService;

    public function boot(ClientDebtService $clientDebtService): void
    {
        $this->clientDebtService = $clientDebtService;
    }

    public function mount(): void
    {
        if (! auth()->user()->can('punto_venta.ver') && ! auth()->user()->can('reporte.ver')) {
            abort(403);
        }
    }

    public function abrirModalCobro(int $debtId): void
    {
        $debt = ClientDebt::query()->find($debtId);
        if (! $debt) {
            $this->flashToast('error', 'Deuda no encontrada.');

            return;
        }

        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->debtIdSeleccionada = $debt->id;
        $this->cobroForm = [
            'monto_pago' => (float) $debt->saldo_pendiente,
            'payment_method_id' => $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
        $this->mostrarModalCobro = true;
    }

    public function cerrarModalCobro(): void
    {
        $this->mostrarModalCobro = false;
        $this->debtIdSeleccionada = null;
        $this->cobroForm = [
            'monto_pago' => 0.00,
            'payment_method_id' => null,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
    }

    public function procesarCobro(): void
    {
        if (! $this->debtIdSeleccionada) {
            $this->flashToast('error', 'No hay una deuda seleccionada.');

            return;
        }

        try {
            $pago = $this->clientDebtService->procesarPago($this->debtIdSeleccionada, $this->cobroForm);
            $this->cerrarModalCobro();
            $this->pagoIdTicket = $pago->id;
            $this->mostrarModalTicketPago = true;
            $this->flashToast('success', 'Cobro registrado correctamente.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function cerrarModalTicketPago(): void
    {
        $this->mostrarModalTicketPago = false;
        $this->pagoIdTicket = null;
    }

    public function render()
    {
        $this->clientDebtService->markOverdueDebts();

        $query = ClientDebt::query()
            ->with(['cliente', 'venta'])
            ->pendientes()
            ->orderByDesc('fecha_registro');

        if ($this->search) {
            $query->whereHas('cliente', fn ($q) => $q->where('nombres', 'like', '%'.$this->search.'%')
                ->orWhere('apellidos', 'like', '%'.$this->search.'%')
                ->orWhere('numero_documento', 'like', '%'.$this->search.'%'));
        }

        if ($this->estadoFilter) {
            $query->where('estado', $this->estadoFilter);
        }

        $debts = $query->paginate($this->perPage);
        $paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();
        $selectedPaymentMethod = ! empty($this->cobroForm['payment_method_id'])
            ? PaymentMethod::find($this->cobroForm['payment_method_id'])
            : null;

        return view('livewire.pos.customer-debts', [
            'debts' => $debts,
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $selectedPaymentMethod,
        ])->layout('layouts.app', ['title' => 'Estado de cuenta - Deudas']);
    }
}
