<?php

namespace App\Services\Legacy;

use App\Models\Core\ClienteMembresia;
use Illuminate\Support\Collection;

/**
 * Punto único de lectura para cliente_membresias (legacy). Sin altas nuevas.
 */
class LegacyMembresiaReadService
{
    /**
     * @return Collection<int, ClienteMembresia>
     */
    public function historyForCliente(int $clienteId, int $limit = 10): Collection
    {
        return ClienteMembresia::with(['membresia', 'pagos', 'asesor'])
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_inicio')
            ->limit($limit)
            ->get();
    }

    public function hasLegacyHistory(int $clienteId): bool
    {
        return ClienteMembresia::query()
            ->where('cliente_id', $clienteId)
            ->exists();
    }

    /**
     * @return array{count: int, saldo_pendiente: float}
     */
    public function summaryForCliente(int $clienteId): array
    {
        $rows = ClienteMembresia::query()
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['activa', 'congelada', 'vencida'])
            ->get();

        $saldo = $rows->sum(function (ClienteMembresia $m) {
            $precio = (float) ($m->precio_final ?? 0);
            $pagado = (float) ($m->monto_pagado_actual ?? 0);

            return max(0, $precio - $pagado);
        });

        return [
            'count' => $rows->count(),
            'saldo_pendiente' => round((float) $saldo, 2),
        ];
    }
}
