<?php

namespace App\Services\Analytics;

use App\Data\Reporte\ReporteSucursalFilter;
use App\Models\Core\ClientDebt;
use App\Services\ClientDebtService;
use App\Support\SucursalScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FinanceAnalyticsService
{
    public function __construct(
        protected ClientDebtService $clientDebtService,
        protected SucursalScope $sucursalScope,
    ) {}

    /**
     * @param  array{search?: string, estado?: string, fecha_inicio?: string, fecha_fin?: string}  $filters
     */
    public function accountsReceivableQuery(array $filters = [], ?ReporteSucursalFilter $scopeFilter = null): Builder
    {
        $query = ClientDebt::query()
            ->with(['cliente', 'venta.usuario', 'sucursal'])
            ->pendientes()
            ->orderByDesc('fecha_registro');

        if ($scopeFilter !== null && ! $scopeFilter->isActive()) {
            $query = $this->sucursalScope->applyReportScope(
                $query,
                $scopeFilter->specificSucursalId(),
                $scopeFilter->isConsolidated()
            );
        }

        if (! empty($filters['search'])) {
            $term = $filters['search'];
            $query->whereHas('cliente', fn ($q) => $q
                ->where('nombres', 'like', '%'.$term.'%')
                ->orWhere('apellidos', 'like', '%'.$term.'%')
                ->orWhere('numero_documento', 'like', '%'.$term.'%')
                ->orWhere('codigo', 'like', '%'.$term.'%')
                ->orWhere('telefono', 'like', '%'.$term.'%'));
        }

        if (! empty($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        if (! empty($filters['fecha_inicio'])) {
            $query->whereDate('fecha_registro', '>=', $filters['fecha_inicio']);
        }

        if (! empty($filters['fecha_fin'])) {
            $query->whereDate('fecha_registro', '<=', $filters['fecha_fin']);
        }

        return $query;
    }

    /**
     * @param  array{search?: string, estado?: string, fecha_inicio?: string, fecha_fin?: string}  $filters
     */
    public function paginateAccountsReceivable(array $filters, int $perPage = 15, ?ReporteSucursalFilter $scopeFilter = null): LengthAwarePaginator
    {
        $this->clientDebtService->markOverdueDebts();

        return $this->accountsReceivableQuery($filters, $scopeFilter)->paginate($perPage);
    }

    /**
     * @param  array{search?: string, estado?: string, fecha_inicio?: string, fecha_fin?: string}  $filters
     * @return array{total_saldo: float, total_registros: int, total_vencido: float, total_pendiente: float}
     */
    public function accountsReceivableSummary(array $filters = [], ?ReporteSucursalFilter $scopeFilter = null): array
    {
        $this->clientDebtService->markOverdueDebts();

        $rows = (clone $this->accountsReceivableQuery($filters, $scopeFilter))->get(['saldo_pendiente', 'estado', 'fecha_vencimiento']);

        $totalSaldo = round((float) $rows->sum('saldo_pendiente'), 2);
        $totalVencido = round((float) $rows
            ->filter(fn (ClientDebt $debt) => $debt->estado === 'vencido' || ($debt->fecha_vencimiento?->isPast() ?? false))
            ->sum('saldo_pendiente'), 2);

        return [
            'total_saldo' => $totalSaldo,
            'total_registros' => $rows->count(),
            'total_vencido' => $totalVencido,
            'total_pendiente' => round($totalSaldo - $totalVencido, 2),
        ];
    }
}
