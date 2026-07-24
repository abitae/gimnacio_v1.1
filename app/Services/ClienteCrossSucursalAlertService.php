<?php

namespace App\Services;

use App\Models\Core\Cliente;
use App\Models\Core\ClientDebt;
use Illuminate\Support\Collection;

class ClienteCrossSucursalAlertService
{
    public function __construct(
        protected SucursalContext $sucursalContext
    ) {}

    /**
     * @return Collection<int, array{sucursal_id: int, sucursal_nombre: string, estado_cliente: string, tiene_deuda: bool, saldo_deuda: float}>
     */
    public function findMatches(?string $tipoDocumento, ?string $numeroDocumento, ?int $excludeClienteId = null): Collection
    {
        $activeSucursalId = $this->sucursalContext->getSucursalId();
        $tipoDocumento = strtoupper(trim((string) $tipoDocumento));
        $numeroDocumento = trim((string) $numeroDocumento);

        if ($activeSucursalId === null || $tipoDocumento === '' || $numeroDocumento === '') {
            return collect();
        }

        $matches = Cliente::withoutGlobalScope('active_sucursal')
            ->with('sucursal:id,nombre')
            ->where('tipo_documento', $tipoDocumento)
            ->where('numero_documento', $numeroDocumento)
            ->where('sucursal_id', '!=', $activeSucursalId)
            ->when($excludeClienteId, fn ($q) => $q->where('id', '!=', $excludeClienteId))
            ->get(['id', 'sucursal_id', 'estado_cliente']);

        return $matches->map(function (Cliente $cliente) {
            $saldo = (float) ClientDebt::withoutGlobalScope('active_sucursal')
                ->where('cliente_id', $cliente->id)
                ->where('sucursal_id', $cliente->sucursal_id)
                ->whereIn('estado', ['pendiente', 'parcial', 'vencida'])
                ->sum('saldo_pendiente');

            return [
                'sucursal_id' => (int) $cliente->sucursal_id,
                'sucursal_nombre' => (string) ($cliente->sucursal?->nombre ?? 'Otra sede'),
                'estado_cliente' => (string) ($cliente->estado_cliente ?? 'desconocido'),
                'tiene_deuda' => $saldo > 0,
                'saldo_deuda' => round($saldo, 2),
            ];
        });
    }

    public function findMatchesForCliente(Cliente $cliente): Collection
    {
        return $this->findMatches(
            $cliente->tipo_documento,
            $cliente->numero_documento,
            $cliente->id
        );
    }
}
