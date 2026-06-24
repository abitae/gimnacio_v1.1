<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\ClientDebt;
use App\Models\Core\PaymentMethod;
use App\Services\ClientDebtService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Bandeja operativa de cobros pendientes (solo transacciones).
 * Vista analítica: ReporteCuentasPorCobrarLive en reportes.cuentas-por-cobrar.
 */
class CustomerDebts extends Component
{
    use FlashesToast;
    use WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public string $fechaInicio = '';

    public string $fechaFin = '';

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

    public bool $mostrarModalPagoCliente = false;

    public ?int $clienteIdSeleccionado = null;

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

        $clienteId = request()->integer('cliente');
        if ($clienteId > 0) {
            $cliente = \App\Models\Core\Cliente::query()->find($clienteId);
            if ($cliente) {
                $this->search = $cliente->codigo
                    ?: $cliente->numero_documento
                    ?: trim($cliente->nombres.' '.$cliente->apellidos);
            }
        }
    }

    public function abrirModalCobro(int $debtId): void
    {
        $this->authorize('punto_venta.ver');
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
        $this->authorize('punto_venta.ver');

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

    public function abrirModalPagoCliente(int $clienteId): void
    {
        $this->authorize('punto_venta.ver');
        $cliente = \App\Models\Core\Cliente::query()->find($clienteId);
        if (! $cliente) {
            $this->flashToast('error', 'Cliente no encontrado.');

            return;
        }

        $debts = $this->clientDebtService->deudasPendientesPorCliente($clienteId);
        if ($debts->isEmpty()) {
            $this->flashToast('info', 'El cliente no tiene deudas pendientes.');

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
        $this->mostrarModalPagoCliente = true;
    }

    public function cerrarModalPagoCliente(): void
    {
        $this->mostrarModalPagoCliente = false;
        $this->clienteIdSeleccionado = null;
    }

    public function procesarPagoCliente(): void
    {
        $this->authorize('punto_venta.ver');

        if (! $this->clienteIdSeleccionado) {
            $this->flashToast('error', 'No hay cliente seleccionado.');

            return;
        }

        try {
            $pagos = $this->clientDebtService->procesarPagoTotalCliente($this->clienteIdSeleccionado, $this->cobroForm);
            $this->cerrarModalPagoCliente();
            $this->pagoIdTicket = $pagos->last()?->id;
            $this->mostrarModalTicketPago = $this->pagoIdTicket !== null;
            $this->flashToast('success', 'Se registró el pago total de la deuda del cliente.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
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

    public function render()
    {
        $this->clientDebtService->markOverdueDebts();

        $query = ClientDebt::query()
            ->with(['cliente', 'venta.usuario'])
            ->pendientes()
            ->orderByDesc('fecha_registro');

        if ($this->search) {
            $query->whereHas('cliente', fn ($q) => $q->where('nombres', 'like', '%'.$this->search.'%')
                ->orWhere('apellidos', 'like', '%'.$this->search.'%')
                ->orWhere('numero_documento', 'like', '%'.$this->search.'%')
                ->orWhere('codigo', 'like', '%'.$this->search.'%')
                ->orWhere('telefono', 'like', '%'.$this->search.'%'));
        }

        if ($this->estadoFilter) {
            $query->where('estado', $this->estadoFilter);
        }

        if ($this->fechaInicio) {
            $query->whereDate('fecha_registro', '>=', $this->fechaInicio);
        }

        if ($this->fechaFin) {
            $query->whereDate('fecha_registro', '<=', $this->fechaFin);
        }

        $debts = $query->paginate($this->perPage);
        $paymentMethods = PaymentMethod::activos()->orderBy('nombre')->get();
        $selectedPaymentMethod = ! empty($this->cobroForm['payment_method_id'])
            ? PaymentMethod::find($this->cobroForm['payment_method_id'])
            : null;
        $clienteSeleccionado = $this->clienteIdSeleccionado
            ? \App\Models\Core\Cliente::query()->find($this->clienteIdSeleccionado)
            : null;
        $totalClienteSeleccionado = $this->clienteIdSeleccionado
            ? (float) $this->clientDebtService->deudasPendientesPorCliente($this->clienteIdSeleccionado)->sum('saldo_pendiente')
            : 0.0;

        return view('livewire.pos.customer-debts', [
            'debts' => $debts,
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'clienteSeleccionado' => $clienteSeleccionado,
            'totalClienteSeleccionado' => $totalClienteSeleccionado,
        ])->layout('layouts.app', ['title' => 'Cobros pendientes']);
    }
}
