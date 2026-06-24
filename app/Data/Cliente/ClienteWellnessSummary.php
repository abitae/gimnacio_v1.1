<?php

namespace App\Data\Cliente;

final readonly class ClienteWellnessSummary
{
    /**
     * @param  array<int, \App\Models\Core\Rental>  $reservasEspacios
     */
    public function __construct(
        public array $reservasEspacios,
        public int $rutinasActivasCount,
        public int $proximasCitasCount,
        public int $evaluacionesRecientesCount,
    ) {}
}
