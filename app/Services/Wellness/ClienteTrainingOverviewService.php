<?php

namespace App\Services\Wellness;

use App\Models\ClientRoutine;
use App\Models\WorkoutSession;
use App\Services\ClientRoutineService;
use Illuminate\Support\Collection;

class ClienteTrainingOverviewService
{
    public function __construct(
        protected ClientRoutineService $clientRoutineService,
    ) {}

    /**
     * @return array{rutinas_activas: Collection, sesiones_recientes: Collection, cumplimiento_pct: float}
     */
    public function getTrainingSummary(int $clienteId): array
    {
        $rutinas = ClientRoutine::with(['routineTemplate', 'trainer'])
            ->where('cliente_id', $clienteId)
            ->where('estado', 'activa')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        $sesiones = WorkoutSession::query()
            ->whereHas('clientRoutine', fn ($q) => $q->where('cliente_id', $clienteId))
            ->orderByDesc('fecha_hora')
            ->limit(10)
            ->get();

        $completadas = $sesiones->where('estado', 'completada')->count();
        $total = max(1, $sesiones->count());

        return [
            'rutinas_activas' => $rutinas,
            'sesiones_recientes' => $sesiones,
            'cumplimiento_pct' => round(($completadas / $total) * 100, 1),
        ];
    }
}
