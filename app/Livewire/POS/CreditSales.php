<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Venta;
use App\Services\ClientDebtService;
use Livewire\Component;
use Livewire\WithPagination;

class CreditSales extends Component
{
    use FlashesToast;
    use WithPagination;

    public string $search = '';

    public string $fechaInicio = '';

    public string $fechaFin = '';

    public int $perPage = 15;

    public bool $mostrarModalCobroVenta = false;

    public bool $mostrarModalCobroCliente = false;

    public bool $mostrarModalTicketPago = false;

    public ?int $debtIdSeleccionada = null;

    public ?int $clienteIdSeleccionado = null;

    public ?int $pagoIdTicket = null;

    public array $cobroForm = [
        'monto_pago' => 0.00,
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected ClientDebtService $clientDebtService;

    public function boot(ClientDebtService $clientDebtService): void
    {
        $this->clientDebtService = $clientDebtService;
    }

    public function mount(): void
    {
        $this->authorize('punto_venta.ver');
        $this->fechaInicio = now()->startOfMonth()->format('Y-m-d');
        $this->fechaFin = now()->format('Y-m-d');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFechaInicio(): void
    {
        $this->resetPage();
    }

    public function updatingFechaFin(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function abrirModalCobroVenta(int $debtId): void
    {
        $debt = ClientDebt::query()->find($debtId);
        if (! $debt) {
            $this->flashToast('error', 'Venta a crédito no disponible.');

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
        $this->mostrarModalCobroVenta = true;
    }

    public function cerrarModalCobroVenta(): void
    {
        $this->mostrarModalCobroVenta = false;
        $this->debtIdSeleccionada = null;
    }

    public function procesarCobroVenta(): void
    {
        if (! $this->debtIdSeleccionada) {
            $this->flashToast('error', 'No hay venta seleccionada.');

            return;
        }

        try {
            $pago = $this->clientDebtService->procesarPago($this->debtIdSeleccionada, $this->cobroForm);
            $this->cerrarModalCobroVenta();
            $this->pagoIdTicket = $pago->id;
            $this->mostrarModalTicketPago = true;
            $this->flashToast('success', 'Pago registrado para la venta a crédito.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function abrirModalCobroCliente(int $clienteId): void
    {
        $debts = $this->clientDebtService->deudasPendientesPorCliente($clienteId);
        if ($debts->isEmpty()) {
            $this->flashToast('info', 'El cliente no tiene deuda pendiente.');

            return;
        }

        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->clienteIdSeleccionado = $clienteId;
        $this->cobroForm = [
            'monto_pago' => (float) $debts->sum('saldo_pendiente'),
            'payment_method_id' => $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
        $this->mostrarModalCobroCliente = true;
    }

    public function cerrarModalCobroCliente(): void
    {
        $this->mostrarModalCobroCliente = false;
        $this->clienteIdSeleccionado = null;
    }

    public function procesarCobroCliente(): void
    {
        if (! $this->clienteIdSeleccionado) {
            $this->flashToast('error', 'No hay cliente seleccionado.');

            return;
        }

        try {
            $pagos = $this->clientDebtService->procesarPagoTotalCliente($this->clienteIdSeleccionado, $this->cobroForm);
            $this->cerrarModalCobroCliente();
            $this->pagoIdTicket = $pagos->last()?->id;
            $this->mostrarModalTicketPago = $this->pagoIdTicket !== null;
            $this->flashToast('success', 'Se registró el pago total de la deuda del cliente.');
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
        $query = Venta::query()
            ->where('es_credito', true)
            ->with(['cliente', 'usuario', 'clientDebt'])
            ->orderByDesc('fecha_venta');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('numero_venta', 'like', '%'.$this->search.'%')
                    ->orWhereHas('cliente', fn ($c) => $c->where('nombres', 'like', '%'.$this->search.'%')
                        ->orWhere('apellidos', 'like', '%'.$this->search.'%')
                        ->orWhere('numero_documento', 'like', '%'.$this->search.'%')
                        ->orWhere('codigo', 'like', '%'.$this->search.'%')
                        ->orWhere('telefono', 'like', '%'.$this->search.'%'));
            });
        }

        if ($this->fechaInicio) {
            $query->whereDate('fecha_venta', '>=', $this->fechaInicio);
        }

        if ($this->fechaFin) {
            $query->whereDate('fecha_venta', '<=', $this->fechaFin);
        }

        $ventas = $query->paginate($this->perPage);
        $paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();
        $selectedPaymentMethod = ! empty($this->cobroForm['payment_method_id'])
            ? PaymentMethod::find($this->cobroForm['payment_method_id'])
            : null;
        $clienteSeleccionado = $this->clienteIdSeleccionado
            ? Cliente::query()->find($this->clienteIdSeleccionado)
            : null;
        $totalClienteSeleccionado = $this->clienteIdSeleccionado
            ? (float) $this->clientDebtService->deudasPendientesPorCliente($this->clienteIdSeleccionado)->sum('saldo_pendiente')
            : 0.0;

        return view('livewire.pos.credit-sales', [
            'ventas' => $ventas,
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'clienteSeleccionado' => $clienteSeleccionado,
            'totalClienteSeleccionado' => $totalClienteSeleccionado,
        ])->layout('layouts.app', ['title' => 'Ventas a crédito']);
    }
}
