<?php

namespace App\Services;

use App\Models\ClientRoutine;
use App\Models\Core\Cita;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Models\Core\Rental;
use App\Models\Core\SeguimientoNutricion;
use Illuminate\Support\Collection;

class ClientWellnessService
{
    public function getGestionOverview(int $clienteId): array
    {
        $rutinas = ClientRoutine::with(['routineTemplate', 'trainer'])
            ->where('cliente_id', $clienteId)
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();

        $reservas = Rental::with('rentableSpace')
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->limit(10)
            ->get();

        $planesGestion = $this->getPlanesGestion($clienteId);

        $lineaTiempo = $this->buildLineaTiempo($clienteId, $rutinas, $reservas, $planesGestion);

        return compact('rutinas', 'reservas', 'planesGestion', 'lineaTiempo');
    }

    public function getPlanesGestion(int $clienteId): Collection
    {
        $matriculas = ClienteMatricula::with(['membresia', 'clase'])
            ->where('cliente_id', $clienteId)
            ->whereIn('estado', ['activa', 'congelada'])
            ->get();

        $planes = collect()
            ->concat($matriculas->map(fn (ClienteMatricula $item) => [
                'key' => 'cliente_matricula:' . $item->id,
                'origen_tipo' => 'cliente_matricula',
                'id' => $item->id,
                'nombre' => $item->nombre,
                'estado' => $item->estado,
                'tipo' => $item->tipo,
                'fecha_matricula' => $item->fecha_matricula,
                'fecha_inicio' => $item->fecha_inicio,
                'fecha_fin' => $item->fecha_fin,
                'fechas_congelacion' => $item->fechas_congelacion ?? [],
            ]));

        if (! $matriculas->contains(fn (ClienteMatricula $item) => $item->tipo === 'membresia')) {
            $planes = $planes->concat(
                ClienteMembresia::with('membresia')
                    ->where('cliente_id', $clienteId)
                    ->whereIn('estado', ['activa', 'congelada'])
                    ->get()
                    ->map(fn (ClienteMembresia $item) => [
                        'key' => 'cliente_membresia:' . $item->id,
                        'origen_tipo' => 'cliente_membresia',
                        'id' => $item->id,
                        'nombre' => $item->membresia?->nombre ?? 'Membresía',
                        'estado' => $item->estado,
                        'tipo' => 'membresia',
                        'fecha_matricula' => $item->fecha_matricula,
                        'fecha_inicio' => $item->fecha_inicio,
                        'fecha_fin' => $item->fecha_fin,
                        'fechas_congelacion' => $item->fechas_congelacion ?? [],
                    ])
            );
        }

        return $planes
            ->sortByDesc(fn (array $item) => optional($item['fecha_inicio'])->timestamp ?? 0)
            ->values();
    }

    public function freezePlan(int $clienteId, array $data, int $userId): void
    {
        $model = $data['origen_tipo'] === 'cliente_membresia'
            ? ClienteMembresia::where('cliente_id', $clienteId)->find($data['registro_id'])
            : ClienteMatricula::where('cliente_id', $clienteId)->find($data['registro_id']);

        if (! $model) {
            throw new \RuntimeException('No se encontró el plan seleccionado.');
        }

        $fechas = collect($model->fechas_congelacion ?? [])
            ->push([
                'desde' => $data['fecha_desde'],
                'hasta' => $data['fecha_hasta'],
                'motivo' => $data['motivo'] ?: null,
                'registrado_por' => $userId,
                'registrado_en' => now()->toDateTimeString(),
            ])
            ->values()
            ->all();

        $model->update([
            'estado' => 'congelada',
            'fechas_congelacion' => $fechas,
        ]);
    }

    public function createReservation(int $clienteId, array $data, int $userId): Rental
    {
        return Rental::create([
            'rentable_space_id' => $data['rentable_space_id'],
            'cliente_id' => $clienteId,
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'precio' => $data['precio'],
            'estado' => $data['estado'],
            'observaciones' => $data['observaciones'] ?: null,
            'registrado_por' => $userId,
        ]);
    }

    protected function buildLineaTiempo(int $clienteId, Collection $rutinas, Collection $reservas, Collection $planesGestion): Collection
    {
        return collect()
            ->concat(
                EvaluacionMedidasNutricion::where('cliente_id', $clienteId)
                    ->latest('created_at')
                    ->limit(10)
                    ->get()
                    ->map(fn ($item) => [
                        'fecha' => $item->created_at,
                        'tipo' => 'evaluacion',
                        'titulo' => 'Evaluación corporal',
                        'descripcion' => $item->objetivo ?: ($item->observaciones ?: 'Evaluación registrada'),
                    ])
            )
            ->concat(
                SeguimientoNutricion::where('cliente_id', $clienteId)
                    ->latest('fecha')
                    ->limit(20)
                    ->get()
                    ->map(fn ($item) => [
                        'fecha' => $item->fecha,
                        'tipo' => 'seguimiento',
                        'titulo' => ucfirst(str_replace('_', ' ', $item->tipo)),
                        'descripcion' => $item->contenido ?: ($item->objetivo ?: 'Seguimiento registrado'),
                    ])
            )
            ->concat(
                Cita::where('cliente_id', $clienteId)
                    ->latest('fecha_hora')
                    ->limit(20)
                    ->get()
                    ->map(fn ($item) => [
                        'fecha' => $item->fecha_hora,
                        'tipo' => 'cita',
                        'titulo' => 'Cita ' . ucfirst(str_replace('_', ' ', $item->tipo)),
                        'descripcion' => $item->estado ? 'Estado: ' . $item->estado : 'Cita registrada',
                    ])
            )
            ->concat(
                $rutinas->map(fn ($item) => [
                    'fecha' => $item->created_at,
                    'tipo' => 'rutina',
                    'titulo' => 'Rutina asignada',
                    'descripcion' => ($item->routineTemplate?->nombre ?? 'Rutina') . ' · ' . ucfirst($item->estado),
                ])
            )
            ->concat(
                $reservas->map(fn ($item) => [
                    'fecha' => $item->fecha,
                    'tipo' => 'reserva',
                    'titulo' => 'Reserva de espacio',
                    'descripcion' => ($item->rentableSpace?->nombre ?? 'Espacio') . ' · ' . ($item->estado ?? 'reservado'),
                ])
            )
            ->concat(
                $planesGestion->flatMap(function (array $plan) {
                    return collect($plan['fechas_congelacion'] ?? [])->map(fn ($periodo) => [
                        'fecha' => ! empty($periodo['desde']) ? \Carbon\Carbon::parse($periodo['desde']) : now(),
                        'tipo' => 'congelamiento',
                        'titulo' => 'Congelamiento de plan',
                        'descripcion' => $plan['nombre'] . (! empty($periodo['motivo']) ? ' · ' . $periodo['motivo'] : ''),
                    ]);
                })
            )
            ->sortByDesc(fn (array $item) => $item['fecha']?->timestamp ?? 0)
            ->take(30)
            ->values();
    }
}
