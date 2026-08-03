<?php

namespace App\Livewire\POS;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\ClientDebt;
use App\Models\Core\Cliente;
use App\Models\Core\PaymentMethod;
use App\Models\Core\Venta;
use App\Services\ClientDebtService;
use App\Services\CreditSalesQueryService;
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

    public bool $mostrarModalCobroMasivo = false;

    public bool $mostrarModalTicketPago = false;

    public ?int $debtIdSeleccionada = null;

    public ?int $clienteIdSeleccionado = null;

    public ?int $pagoIdTicket = null;

    public array $deudasSeleccionadas = [];

    public array $cobroForm = [
        'monto_pago' => 0.00,
        'payment_method_id' => null,
        'numero_operacion' => '',
        'entidad_financiera' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected ClientDebtService $clientDebtService;

    protected CreditSalesQueryService $creditSalesQueryService;

    public function boot(ClientDebtService $clientDebtService, CreditSalesQueryService $creditSalesQueryService): void
    {
        $this->clientDebtService = $clientDebtService;
        $this->creditSalesQueryService = $creditSalesQueryService;
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
        $this->deudasSeleccionadas = [];
    }

    public function updatingFechaInicio(): void
    {
        $this->resetPage();
        $this->deudasSeleccionadas = [];
    }

    public function updatingFechaFin(): void
    {
        $this->resetPage();
        $this->deudasSeleccionadas = [];
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
        $this->deudasSeleccionadas = [];
    }

    public function alternarDeudaSeleccionada(int $debtId): void
    {
        $debtId = (int) $debtId;
        $seleccionadas = $this->deudasSeleccionadasNormalizadas();

        if (in_array($debtId, $seleccionadas, true)) {
            $this->deudasSeleccionadas = array_values(array_filter(
                $seleccionadas,
                fn (int $id) => $id !== $debtId
            ));

            return;
        }

        $seleccionadas[] = $debtId;
        $this->deudasSeleccionadas = array_values(array_unique($seleccionadas));
    }

    public function alternarSeleccionPaginaActual(): void
    {
        $idsPagina = $this->debtIdsCobrablesPaginaActual();
        if ($idsPagina === []) {
            return;
        }

        $seleccionadas = $this->deudasSeleccionadasNormalizadas();
        $paginaYaSeleccionada = count(array_intersect($idsPagina, $seleccionadas)) === count($idsPagina);

        if ($paginaYaSeleccionada) {
            $this->deudasSeleccionadas = array_values(array_diff($seleccionadas, $idsPagina));

            return;
        }

        $this->deudasSeleccionadas = array_values(array_unique(array_merge($seleccionadas, $idsPagina)));
    }

    public function seleccionarPaginaActual(): void
    {
        $idsPagina = $this->debtIdsCobrablesPaginaActual();
        if ($idsPagina === []) {
            return;
        }

        $this->deudasSeleccionadas = array_values(array_unique(array_merge(
            $this->deudasSeleccionadasNormalizadas(),
            $idsPagina
        )));
    }

    public function limpiarSeleccion(): void
    {
        $this->deudasSeleccionadas = [];
    }

    /**
     * @return list<int>
     */
    protected function deudasSeleccionadasNormalizadas(): array
    {
        return collect($this->deudasSeleccionadas)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    protected function debtIdsCobrablesPaginaActual(): array
    {
        $query = $this->creditSalesQueryService->query(
            $this->search ?: null,
            $this->fechaInicio ?: null,
            $this->fechaFin ?: null,
        );
        $ventas = $query->paginate($this->perPage, ['*'], 'page', $this->getPage());

        return $this->creditSalesQueryService->debtIdsCobrablesEnPagina($ventas->getCollection());
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
            $debtId = $this->debtIdSeleccionada;
            $pago = $this->clientDebtService->procesarPago($debtId, $this->cobroForm);
            $this->cerrarModalCobroVenta();
            $this->deudasSeleccionadas = array_values(array_filter(
                $this->deudasSeleccionadasNormalizadas(),
                fn (int $id) => $id !== (int) $debtId
            ));
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
            $this->limpiarSeleccion();
            $this->pagoIdTicket = $pagos->last()?->id;
            $this->mostrarModalTicketPago = $this->pagoIdTicket !== null;
            $this->flashToast('success', 'Se registró el pago total de la deuda del cliente.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function abrirModalCobroMasivo(): void
    {
        if ($this->deudasSeleccionadasNormalizadas() === []) {
            $this->flashToast('error', 'Seleccione al menos una venta con saldo pendiente.');

            return;
        }

        $efectivo = PaymentMethod::activos()->where('nombre', 'Efectivo')->first();
        $this->cobroForm = [
            'monto_pago' => 0,
            'payment_method_id' => $efectivo?->id ?? PaymentMethod::activos()->orderBy('nombre')->first()?->id,
            'numero_operacion' => '',
            'entidad_financiera' => '',
        ];
        $this->mostrarModalCobroMasivo = true;
    }

    public function cerrarModalCobroMasivo(): void
    {
        $this->mostrarModalCobroMasivo = false;
    }

    public function procesarCobroMasivo(): void
    {
        $seleccionadas = $this->deudasSeleccionadasNormalizadas();
        if ($seleccionadas === []) {
            $this->flashToast('error', 'Seleccione al menos una venta con saldo pendiente.');

            return;
        }

        try {
            $pagos = $this->clientDebtService->procesarPagoMasivo($seleccionadas, $this->cobroForm);
            $cantidad = $pagos->count();
            $this->cerrarModalCobroMasivo();
            $this->limpiarSeleccion();
            $this->pagoIdTicket = $pagos->last()?->id;
            $this->mostrarModalTicketPago = $this->pagoIdTicket !== null;
            $this->flashToast('success', "Se registraron {$cantidad} pago(s) de las ventas seleccionadas.");
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
        $query = $this->creditSalesQueryService->query(
            $this->search ?: null,
            $this->fechaInicio ?: null,
            $this->fechaFin ?: null,
        );

        $ventas = $query->paginate($this->perPage);
        $totales = $this->creditSalesQueryService->totales($query);
        $filasVentas = $ventas->getCollection()->mapWithKeys(fn (Venta $venta) => [
            $venta->id => $this->creditSalesQueryService->mapVenta($venta),
        ]);

        $deudasSeleccionadas = $this->deudasSeleccionadasNormalizadas();

        $totalSeleccionado = ClientDebt::query()
            ->whereIn('id', $deudasSeleccionadas)
            ->pendientes()
            ->sum('saldo_pendiente');

        $debtIdsPagina = $this->creditSalesQueryService->debtIdsCobrablesEnPagina($ventas->getCollection());
        $paginaCompletaSeleccionada = $debtIdsPagina !== []
            && count(array_intersect($debtIdsPagina, $deudasSeleccionadas)) === count($debtIdsPagina);

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

        $exportParams = array_filter([
            'search' => $this->search ?: null,
            'fecha_inicio' => $this->fechaInicio ?: null,
            'fecha_fin' => $this->fechaFin ?: null,
        ]);

        return view('livewire.pos.credit-sales', [
            'ventas' => $ventas,
            'filasVentas' => $filasVentas,
            'totales' => $totales,
            'totalSeleccionado' => (float) $totalSeleccionado,
            'deudasSeleccionadas' => $deudasSeleccionadas,
            'cantidadSeleccionadas' => count($deudasSeleccionadas),
            'debtIdsPagina' => $debtIdsPagina,
            'paginaCompletaSeleccionada' => $paginaCompletaSeleccionada,
            'exportUrl' => route('pos.ventas-credito.exportar.excel', $exportParams),
            'paymentMethods' => $paymentMethods,
            'selectedPaymentMethod' => $selectedPaymentMethod,
            'clienteSeleccionado' => $clienteSeleccionado,
            'totalClienteSeleccionado' => $totalClienteSeleccionado,
        ])->layout('layouts.app', ['title' => 'Ventas a crédito']);
    }
}
