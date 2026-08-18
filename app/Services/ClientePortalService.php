<?php

namespace App\Services;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use App\Support\ClientePortalQuery;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClientePortalService
{
    public function __construct(protected DailyOperationsDebtService $debtService) {}

    public function cliente(int $clienteId): Cliente
    {
        return ClientePortalQuery::clientes()
            ->with('sucursal')
            ->findOrFail($clienteId);
    }

    /**
     * @return Collection<int, ClienteMatricula>
     */
    public function membresias(int $clienteId): Collection
    {
        return ClientePortalQuery::matriculas()
            ->with(['membresia', 'clase', 'sucursal'])
            ->where('cliente_id', $clienteId)
            ->whereIn('tipo', ['membresia', 'clase'])
            ->orderByRaw("CASE WHEN estado = 'activa' THEN 0 ELSE 1 END")
            ->orderByDesc('fecha_fin')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    public function pagosPendientes(int $clienteId): array
    {
        return $this->debtService->summarizeCliente($clienteId);
    }

    public function pagos(int $clienteId, int $perPage = 20): LengthAwarePaginator
    {
        return ClientePortalQuery::pagos()
            ->with([
                'paymentMethod',
                'clienteMatricula.membresia',
                'clienteMatricula.clase',
                'clienteMembresia.membresia',
                'enrollmentInstallment.clienteMatricula',
                'clientDebt',
            ])
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
