<?php

namespace App\Services;

use App\Models\ClientRoutine;
use App\Models\Core\Cita;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\ClienteMembresia;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Models\Core\Rental;
use App\Models\Core\SeguimientoNutricion;
use Carbon\Carbon;
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
                'key' => 'cliente_matricula:'.$item->id,
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
                        'key' => 'cliente_membresia:'.$item->id,
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
        $model = $this->resolvePlanModelForFreeze($clienteId, $data);

        $desde = Carbon::parse($data['fecha_desde'])->startOfDay();
        $hasta = Carbon::parse($data['fecha_hasta'])->startOfDay();
        if ($hasta->lt($desde)) {
            throw new \InvalidArgumentException('La fecha hasta debe ser posterior o igual a la fecha desde.');
        }

        $this->applyFreezePeriod(
            $model,
            $desde->toDateString(),
            $hasta->toDateString(),
            $data['motivo'] ?? null,
            $userId
        );
    }

    /**
     * Congela desde hoy por N días (inclusive): equivale a fecha_desde=hoy, fecha_hasta=hoy+N-1.
     *
     * @param  'cliente_matricula'|'cliente_membresia'  $origenTipo
     */
    public function freezePlanByDays(int $clienteId, string $origenTipo, int $registroId, int $dias, ?string $motivo, int $userId): void
    {
        if ($dias < 1) {
            throw new \InvalidArgumentException('El número de días debe ser al menos 1.');
        }

        $model = $this->resolvePlanModelForFreeze($clienteId, [
            'origen_tipo' => $origenTipo,
            'registro_id' => $registroId,
        ]);

        $membresia = $model->membresia;
        if ($membresia) {
            if (! $membresia->permite_congelacion) {
                throw new \InvalidArgumentException('Esta membresía no permite congelación.');
            }
            if ($membresia->max_dias_congelacion !== null && $dias > (int) $membresia->max_dias_congelacion) {
                throw new \InvalidArgumentException('El periodo supera los días máximos de congelación permitidos.');
            }
        }

        $desde = Carbon::today()->startOfDay();
        $hasta = $desde->copy()->addDays($dias - 1)->startOfDay();

        $this->applyFreezePeriod(
            $model,
            $desde->toDateString(),
            $hasta->toDateString(),
            $motivo,
            $userId,
            $dias
        );
    }

    protected function applyFreezePeriod(
        ClienteMatricula|ClienteMembresia $model,
        string $fechaDesde,
        string $fechaHasta,
        ?string $motivo,
        int $userId,
        ?int $diasSolicitados = null
    ): void {
        $desde = Carbon::parse($fechaDesde)->startOfDay();
        $hasta = Carbon::parse($fechaHasta)->startOfDay();
        if ($hasta->lt($desde)) {
            throw new \InvalidArgumentException('La fecha hasta debe ser posterior o igual a la fecha desde.');
        }

        $dias = (int) $desde->diffInDays($hasta) + 1;

        $membresia = $model->membresia;
        if ($membresia) {
            if (! $membresia->permite_congelacion) {
                throw new \InvalidArgumentException('Esta membresía no permite congelación.');
            }
            if ($membresia->max_dias_congelacion !== null && $dias > (int) $membresia->max_dias_congelacion) {
                throw new \InvalidArgumentException('El periodo supera los días máximos de congelación permitidos.');
            }
        }

        $entry = [
            'desde' => $fechaDesde,
            'hasta' => $fechaHasta,
            'motivo' => $motivo ?: null,
            'registrado_por' => $userId,
            'registrado_en' => now()->toDateTimeString(),
        ];
        if ($diasSolicitados !== null) {
            $entry['dias'] = $diasSolicitados;
        }

        $fechas = collect($model->fechas_congelacion ?? [])
            ->push($entry)
            ->values()
            ->all();

        $attrs = [
            'estado' => 'congelada',
            'fechas_congelacion' => $fechas,
        ];

        if ($model->fecha_fin) {
            $attrs['fecha_fin'] = Carbon::parse($model->fecha_fin)->addDays($dias)->toDateString();
        }

        $model->update($attrs);

        if ($model instanceof ClienteMatricula) {
            app(EnrollmentInstallmentService::class)->shiftPendingInstallmentsForMatricula($model->fresh(), $dias);
        }
    }

    /**
     * @param  array{origen_tipo: string, registro_id: int}  $data
     */
    protected function resolvePlanModelForFreeze(int $clienteId, array $data): ClienteMatricula|ClienteMembresia
    {
        $model = $data['origen_tipo'] === 'cliente_membresia'
            ? ClienteMembresia::with('membresia')->where('cliente_id', $clienteId)->find($data['registro_id'])
            : ClienteMatricula::with('membresia')->where('cliente_id', $clienteId)->find($data['registro_id']);

        if (! $model) {
            throw new \RuntimeException('No se encontró el plan seleccionado.');
        }

        return $model;
    }

    /**
     * Valida que no exista solapamiento con otras reservas del mismo espacio (excl. canceladas).
     *
     * @throws \InvalidArgumentException
     */
    public function assertReservationSlotAvailable(
        int $rentableSpaceId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $exceptRentalId = null
    ): void {
        $horaInicioCarbon = Carbon::parse($fecha.' '.$horaInicio);
        $horaFinCarbon = Carbon::parse($fecha.' '.$horaFin);
        if ($horaFinCarbon <= $horaInicioCarbon) {
            throw new \InvalidArgumentException('La hora fin debe ser posterior a la hora de inicio.');
        }

        $solapado = Rental::query()
            ->where('rentable_space_id', $rentableSpaceId)
            ->whereDate('fecha', $fecha)
            ->whereNotIn('estado', ['cancelado'])
            ->when($exceptRentalId, fn ($q) => $q->where('id', '!=', $exceptRentalId))
            ->where(function ($q) use ($horaInicioCarbon, $horaFinCarbon) {
                $q->where(function ($q2) use ($horaInicioCarbon, $horaFinCarbon) {
                    $q2->where('hora_inicio', '<', $horaFinCarbon->format('H:i:s'))
                        ->where('hora_fin', '>', $horaInicioCarbon->format('H:i:s'));
                });
            })
            ->exists();

        if ($solapado) {
            throw new \InvalidArgumentException('El horario se solapa con otra reserva.');
        }
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

    /**
     * Próximas reservas (fecha >= hoy, no canceladas) e historial reciente.
     *
     * @return array{upcoming: \Illuminate\Support\Collection, past: \Illuminate\Support\Collection}
     */
    public function listReservationsForCliente(int $clienteId, int $upcomingLimit = 15, int $pastLimit = 15): array
    {
        $hoy = now()->toDateString();

        $upcoming = Rental::with('rentableSpace')
            ->where('cliente_id', $clienteId)
            ->whereDate('fecha', '>=', $hoy)
            ->whereNotIn('estado', ['cancelado'])
            ->orderBy('fecha')
            ->orderBy('hora_inicio')
            ->limit($upcomingLimit)
            ->get();

        $past = Rental::with('rentableSpace')
            ->where('cliente_id', $clienteId)
            ->where(function ($q) use ($hoy) {
                $q->whereDate('fecha', '<', $hoy)
                    ->orWhereIn('estado', ['cancelado', 'finalizado']);
            })
            ->orderByDesc('fecha')
            ->orderByDesc('hora_inicio')
            ->limit($pastLimit)
            ->get();

        return ['upcoming' => $upcoming, 'past' => $past];
    }

    /**
     * Una sola lista: próximas en orden cronológico y luego historial reciente, sin duplicar por id.
     *
     * @return Collection<int, Rental>
     */
    public function listReservationsUnifiedForCliente(int $clienteId, int $upcomingLimit = 15, int $pastLimit = 15): Collection
    {
        $split = $this->listReservationsForCliente($clienteId, $upcomingLimit, $pastLimit);

        return $split['upcoming']
            ->concat($split['past'])
            ->unique('id')
            ->values();
    }

    public function updateClienteReservation(Rental $rental, int $clienteId, array $data): Rental
    {
        if ((int) $rental->cliente_id !== $clienteId) {
            throw new \InvalidArgumentException('La reserva no pertenece a este cliente.');
        }

        $rental->update([
            'rentable_space_id' => $data['rentable_space_id'],
            'fecha' => $data['fecha'],
            'hora_inicio' => $data['hora_inicio'],
            'hora_fin' => $data['hora_fin'],
            'precio' => $data['precio'],
            'estado' => $data['estado'],
            'observaciones' => $data['observaciones'] ?: null,
        ]);

        return $rental->fresh('rentableSpace');
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
                        'titulo' => 'Cita '.ucfirst(str_replace('_', ' ', $item->tipo)),
                        'descripcion' => $item->estado ? 'Estado: '.$item->estado : 'Cita registrada',
                    ])
            )
            ->concat(
                $rutinas->map(fn ($item) => [
                    'fecha' => $item->created_at,
                    'tipo' => 'rutina',
                    'titulo' => 'Rutina asignada',
                    'descripcion' => ($item->routineTemplate?->nombre ?? 'Rutina').' · '.ucfirst($item->estado),
                ])
            )
            ->concat(
                $reservas->map(fn ($item) => [
                    'fecha' => $item->fecha,
                    'tipo' => 'reserva',
                    'titulo' => 'Reserva de espacio',
                    'descripcion' => ($item->rentableSpace?->nombre ?? 'Espacio').' · '.($item->estado ?? 'reservado'),
                ])
            )
            ->concat(
                $planesGestion->flatMap(function (array $plan) {
                    return collect($plan['fechas_congelacion'] ?? [])->map(fn ($periodo) => [
                        'fecha' => ! empty($periodo['desde']) ? \Carbon\Carbon::parse($periodo['desde']) : now(),
                        'tipo' => 'congelamiento',
                        'titulo' => 'Congelamiento de plan',
                        'descripcion' => $plan['nombre'].(! empty($periodo['motivo']) ? ' · '.$periodo['motivo'] : ''),
                    ]);
                })
            )
            ->sortByDesc(fn (array $item) => $item['fecha']?->timestamp ?? 0)
            ->take(30)
            ->values();
    }
}
