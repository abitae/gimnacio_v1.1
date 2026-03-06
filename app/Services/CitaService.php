<?php

namespace App\Services;

use App\Models\Core\Cita;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CitaService
{
    /**
     * Obtener todas las citas de un cliente con paginación
     */
    public function getByCliente(int $clienteId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Cita::query()
            ->with(['cliente', 'nutricionista', 'trainerUser', 'evaluacionMedidasNutricion', 'creadoPor'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha_hora', 'desc');

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (isset($filtros['fecha_desde'])) {
            $query->where('fecha_hora', '>=', $filtros['fecha_desde']);
        }

        if (isset($filtros['fecha_hasta'])) {
            $query->where('fecha_hora', '<=', $filtros['fecha_hasta']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener citas por nutricionista
     */
    public function getByNutricionista(int $nutricionistaId, ?Carbon $fecha = null): Collection
    {
        $query = Cita::query()
            ->with(['cliente', 'evaluacionMedidasNutricion'])
            ->where('nutricionista_id', $nutricionistaId)
            ->whereIn('estado', ['programada', 'confirmada', 'en_curso']);

        if ($fecha) {
            $query->whereDate('fecha_hora', $fecha->toDateString());
        }

        return $query->orderBy('fecha_hora', 'asc')->get();
    }

    /**
     * Obtener citas por trainer
     */
    public function getByTrainer(int $trainerId, ?Carbon $fecha = null): Collection
    {
        $query = Cita::query()
            ->with(['cliente', 'evaluacionMedidasNutricion'])
            ->where('trainer_user_id', $trainerId)
            ->whereIn('estado', ['programada', 'confirmada', 'en_curso']);

        if ($fecha) {
            $query->whereDate('fecha_hora', $fecha->toDateString());
        }

        return $query->orderBy('fecha_hora', 'asc')->get();
    }

    /**
     * Obtener una cita por ID
     */
    public function find(int $id): ?Cita
    {
        return Cita::with(['cliente', 'nutricionista', 'trainerUser', 'evaluacionMedidasNutricion', 'creadoPor', 'actualizadoPor'])->find($id);
    }

    /**
     * Crear una nueva cita
     */
    public function create(array $data): Cita
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            // Validar disponibilidad
            $fechaHora = Carbon::parse($validated['fecha_hora']);
            $duracion = $validated['duracion_minutos'] ?? 60;

            if (!$this->validarDisponibilidad(
                $validated['nutricionista_id'] ?? null,
                $validated['trainer_user_id'] ?? null,
                $fechaHora,
                $duracion
            )) {
                throw new \Exception('No hay disponibilidad en el horario seleccionado.');
            }

            $cita = Cita::create($validated);

            return $cita->fresh(['cliente', 'nutricionista', 'trainerUser', 'creadoPor']);
        });
    }

    /**
     * Actualizar una cita
     */
    public function update(int $id, array $data): Cita
    {
        $cita = $this->find($id);

        if (!$cita) {
            throw new \Exception('Cita no encontrada');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($cita, $validated) {
            // Validar disponibilidad si se cambia fecha/hora o profesional
            if (isset($validated['fecha_hora']) || isset($validated['nutricionista_id']) || isset($validated['trainer_user_id'])) {
                $fechaHora = Carbon::parse($validated['fecha_hora'] ?? $cita->fecha_hora);
                $duracion = $validated['duracion_minutos'] ?? $cita->duracion_minutos;
                $nutricionistaId = $validated['nutricionista_id'] ?? $cita->nutricionista_id;
                $trainerUserId = $validated['trainer_user_id'] ?? $cita->trainer_user_id;

                if (!$this->validarDisponibilidad($nutricionistaId, $trainerUserId, $fechaHora, $duracion, $cita->id)) {
                    throw new \Exception('No hay disponibilidad en el horario seleccionado.');
                }
            }

            $cita->update($validated);
            return $cita->fresh(['cliente', 'nutricionista', 'trainerUser', 'actualizadoPor']);
        });
    }

    /**
     * Eliminar una cita
     */
    public function delete(int $id): bool
    {
        $cita = $this->find($id);

        if (!$cita) {
            throw new \Exception('Cita no encontrada');
        }

        // Solo permitir eliminar citas en estados específicos
        if (!in_array($cita->estado, ['programada', 'cancelada'])) {
            throw new \Exception('No se puede eliminar una cita que está ' . $cita->estado);
        }

        return DB::transaction(function () use ($cita) {
            return $cita->delete();
        });
    }

    /**
     * Validar disponibilidad de un profesional
     */
    public function validarDisponibilidad(?int $nutricionistaId, ?int $trainerId, Carbon $fechaHora, int $duracion, ?int $excluirCitaId = null): bool
    {
        if (!$nutricionistaId && !$trainerId) {
            throw new \Exception('Debe especificar un nutricionista o un trainer.');
        }

        $fechaFin = $fechaHora->copy()->addMinutes($duracion);

        $query = Cita::query()
            ->whereIn('estado', ['programada', 'confirmada', 'en_curso'])
            ->where(function ($q) use ($fechaHora, $fechaFin) {
                $q->whereBetween('fecha_hora', [$fechaHora, $fechaFin])
                    ->orWhere(function ($q2) use ($fechaHora, $fechaFin) {
                        $q2->where('fecha_hora', '<=', $fechaHora)
                            ->whereRaw('DATE_ADD(fecha_hora, INTERVAL duracion_minutos MINUTE) >= ?', [$fechaHora]);
                    });
            });

        if ($nutricionistaId) {
            $query->where('nutricionista_id', $nutricionistaId);
        }

        if ($trainerId) {
            $query->where('trainer_user_id', $trainerId);
        }

        if ($excluirCitaId) {
            $query->where('id', '!=', $excluirCitaId);
        }

        return $query->count() === 0;
    }

    /**
     * Cancelar una cita
     */
    public function cancelar(int $id, ?string $motivo = null): Cita
    {
        $cita = $this->find($id);

        if (!$cita) {
            throw new \Exception('Cita no encontrada');
        }

        if (!$cita->puedeCancelar()) {
            throw new \Exception('No se puede cancelar una cita que está ' . $cita->estado);
        }

        return DB::transaction(function () use ($cita, $motivo) {
            $cita->estado = 'cancelada';
            if ($motivo) {
                $cita->observaciones = ($cita->observaciones ? $cita->observaciones . "\n" : '') . "Cancelada: " . $motivo;
            }
            $cita->save();

            return $cita->fresh();
        });
    }

    /**
     * Completar una cita
     */
    public function completar(int $id, ?int $evaluacionMedidasNutricionId = null): Cita
    {
        $cita = $this->find($id);

        if (!$cita) {
            throw new \Exception('Cita no encontrada');
        }

        return DB::transaction(function () use ($cita, $evaluacionMedidasNutricionId) {
            $cita->estado = 'completada';
            if ($evaluacionMedidasNutricionId) {
                $cita->evaluacion_medidas_nutricion_id = $evaluacionMedidasNutricionId;
            }
            $cita->save();

            return $cita->fresh(['evaluacionMedidasNutricion']);
        });
    }

    /**
     * Obtener citas en un rango de fechas para FullCalendar (eventos).
     *
     * @return SupportCollection<int, array{id: int, title: string, start: string, end: string, extendedProps: array}>
     */
    public function getEventosParaCalendario(string $start, string $end): SupportCollection
    {
        $citas = Cita::query()
            ->with(['cliente', 'nutricionista', 'trainerUser'])
            ->whereBetween('fecha_hora', [$start, $end])
            ->orderBy('fecha_hora')
            ->get();

        $coloresPorEstado = [
            'programada' => '#3b82f6',   // blue-500
            'confirmada' => '#22c55e',   // green-500
            'en_curso' => '#f59e0b',     // amber-500
            'completada' => '#64748b',   // slate-500
            'cancelada' => '#ef4444',    // red-500
            'no_asistio' => '#f97316',   // orange-500
        ];

        return $citas->map(function (Cita $cita) use ($coloresPorEstado) {
            $fin = Carbon::parse($cita->fecha_hora)->addMinutes($cita->duracion_minutos ?? 60);
            $clienteNombre = $cita->cliente ? trim($cita->cliente->nombres . ' ' . $cita->cliente->apellidos) : 'Sin cliente';
            $estado = $cita->estado ?? 'programada';
            $color = $coloresPorEstado[$estado] ?? '#6b7280';
            return [
                'id' => $cita->id,
                'title' => $clienteNombre . ' - ' . $cita->tipo,
                'start' => $cita->fecha_hora->toIso8601String(),
                'end' => $fin->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'cliente_id' => $cita->cliente_id,
                    'cliente_nombre' => $clienteNombre,
                    'tipo' => $cita->tipo,
                    'estado' => $estado,
                    'nutricionista_id' => $cita->nutricionista_id,
                    'trainer_user_id' => $cita->trainer_user_id,
                ],
            ];
        });
    }

    /**
     * Validar datos de la cita
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $rules = [
            'cliente_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:clientes,id'],
            'tipo' => ['nullable', 'string', 'in:evaluacion,consulta_nutricional,seguimiento,otro'],
            'fecha_hora' => [$isUpdate ? 'sometimes' : 'required', 'date'],
            'duracion_minutos' => ['nullable', 'integer', 'min:15', 'max:480'],
            'nutricionista_id' => ['nullable', 'exists:users,id'],
            'trainer_user_id' => ['nullable', 'exists:users,id'],
            'estado' => ['nullable', 'string', 'in:programada,confirmada,en_curso,completada,cancelada,no_asistio'],
            'observaciones' => ['nullable', 'string'],
            'evaluacion_medidas_nutricion_id' => ['nullable', 'exists:evaluaciones_medidas_nutricion,id'],
            'created_by' => [$isUpdate ? 'sometimes' : 'required', 'exists:users,id'],
            'updated_by' => ['nullable', 'exists:users,id'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
