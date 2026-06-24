<?php

namespace App\Data\Cliente;

final readonly class ClienteOperationsSummary
{
    /**
     * @param  array<string, mixed>  $operacionDiariaDebtSummary
     * @param  array<int, mixed>  $asistenciasRecientes
     * @param  array<string, mixed>  $estadisticasAsistencia
     * @param  array<string, mixed>  $validacionAcceso
     * @param  array<int, \App\Models\Core\Pago>  $pagosRecientes
     */
    public function __construct(
        public mixed $membresiaActiva,
        public array $operacionDiariaDebtSummary,
        public float $saldoPendiente,
        public float $deudaProductoPendiente,
        public float $deudaMembresiaPendiente,
        public array $asistenciasRecientes,
        public array $estadisticasAsistencia,
        public array $validacionAcceso,
        public mixed $ingresoEnCurso,
        public array $pagosRecientes,
    ) {}
}
