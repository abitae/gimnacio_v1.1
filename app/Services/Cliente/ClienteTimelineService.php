<?php

namespace App\Services\Cliente;

use App\Models\Core\Cita;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Models\Core\Pago;
use App\Models\WorkoutSession;
use Illuminate\Support\Collection;

class ClienteTimelineService
{
    /**
     * @return Collection<int, array{tipo: string, fecha: mixed, titulo: string, meta: array}>
     */
    public function getTimeline(int $clienteId, int $limit = 30): Collection
    {
        $events = collect();

        EvaluacionMedidasNutricion::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get()
            ->each(fn ($row) => $events->push([
                'tipo' => 'evaluacion',
                'fecha' => $row->created_at,
                'titulo' => 'Evaluación corporal',
                'meta' => ['id' => $row->id],
            ]));

        Cita::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_hora')
            ->limit($limit)
            ->get()
            ->each(fn ($row) => $events->push([
                'tipo' => 'cita',
                'fecha' => $row->fecha_hora,
                'titulo' => $row->motivo ?? 'Cita',
                'meta' => ['id' => $row->id],
            ]));

        WorkoutSession::query()
            ->whereHas('clientRoutine', fn ($q) => $q->where('cliente_id', $clienteId))
            ->orderByDesc('fecha_hora')
            ->limit($limit)
            ->get()
            ->each(fn ($row) => $events->push([
                'tipo' => 'sesion',
                'fecha' => $row->fecha_hora ?? $row->created_at,
                'titulo' => 'Sesión de entrenamiento',
                'meta' => ['id' => $row->id],
            ]));

        Pago::query()
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_pago')
            ->limit($limit)
            ->get()
            ->each(fn ($row) => $events->push([
                'tipo' => 'pago',
                'fecha' => $row->fecha_pago,
                'titulo' => 'Pago registrado',
                'meta' => ['id' => $row->id, 'monto' => $row->monto],
            ]));

        return $events
            ->sortByDesc(fn (array $e) => optional($e['fecha'])->timestamp ?? 0)
            ->take($limit)
            ->values();
    }
}
