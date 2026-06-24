<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteFidelitySummary;
use App\Models\Core\ClienteFidelizacionMensaje;

class ClienteFidelityProfileService
{
    public function getSummary(int $clienteId): ClienteFidelitySummary
    {
        $mensajes = ClienteFidelizacionMensaje::query()
            ->where('cliente_id', $clienteId)
            ->with('autor')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->all();

        return new ClienteFidelitySummary(mensajes: $mensajes);
    }
}
