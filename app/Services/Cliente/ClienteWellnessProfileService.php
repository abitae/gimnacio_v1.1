<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteWellnessSummary;
use App\Models\ClientRoutine;
use App\Models\Core\Cita;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Services\ClientWellnessService;

/**
 * Resumen de bienestar (solo lectura). Sin lógica de congelamiento comercial.
 */
class ClienteWellnessProfileService
{
    public function __construct(
        protected ClientWellnessService $clientWellnessService,
    ) {}

    public function getSummary(int $clienteId): ClienteWellnessSummary
    {
        $reservas = $this->clientWellnessService
            ->listReservationsUnifiedForCliente($clienteId)
            ->all();

        $rutinasActivasCount = ClientRoutine::query()
            ->where('cliente_id', $clienteId)
            ->where('estado', 'activa')
            ->count();

        $proximasCitasCount = Cita::query()
            ->where('cliente_id', $clienteId)
            ->whereDate('fecha_hora', '>=', now()->toDateString())
            ->count();

        $evaluacionesRecientesCount = EvaluacionMedidasNutricion::query()
            ->where('cliente_id', $clienteId)
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();

        return new ClienteWellnessSummary(
            reservasEspacios: $reservas,
            rutinasActivasCount: $rutinasActivasCount,
            proximasCitasCount: $proximasCitasCount,
            evaluacionesRecientesCount: $evaluacionesRecientesCount,
        );
    }
}
