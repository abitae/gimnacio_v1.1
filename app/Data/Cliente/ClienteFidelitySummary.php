<?php

namespace App\Data\Cliente;

final readonly class ClienteFidelitySummary
{
    /**
     * @param  array<int, \App\Models\Core\ClienteFidelizacionMensaje>  $mensajes
     */
    public function __construct(
        public array $mensajes,
    ) {}
}
