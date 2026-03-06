<?php

namespace App\Services;

use App\Models\Core\EvaluacionMedidasNutricion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class EvaluacionMedidasNutricionService
{
    /**
     * Obtener todas las evaluaciones de un cliente con paginación
     */
    public function getByCliente(int $clienteId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EvaluacionMedidasNutricion::query()
            ->with(['cliente', 'nutricionista', 'evaluadoPor'])
            ->where('cliente_id', $clienteId)
            ->orderBy('created_at', 'desc');

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['nutricionista_id'])) {
            $query->where('nutricionista_id', $filtros['nutricionista_id']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener una evaluación por ID
     */
    public function find(int $id): ?EvaluacionMedidasNutricion
    {
        return EvaluacionMedidasNutricion::with(['cliente', 'nutricionista', 'evaluadoPor'])->find($id);
    }

    /**
     * Crear una nueva evaluación
     */
    public function create(array $data): EvaluacionMedidasNutricion
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            // Calcular IMC si no está presente
            if (!isset($validated['imc']) && isset($validated['peso']) && isset($validated['estatura']) && $validated['estatura'] > 0) {
                $validated['imc'] = $this->calcularIMC($validated['peso'], $validated['estatura']);
            }

            // Calcular composición corporal si faltan datos
            if (isset($validated['peso'])) {
                $composicion = $this->calcularComposicionCorporal($validated);
                $validated = array_merge($validated, $composicion);
            }

            $evaluacion = EvaluacionMedidasNutricion::create($validated);

            return $evaluacion->fresh(['cliente', 'nutricionista', 'evaluadoPor']);
        });
    }

    /**
     * Actualizar una evaluación
     */
    public function update(int $id, array $data): EvaluacionMedidasNutricion
    {
        $evaluacion = $this->find($id);

        if (!$evaluacion) {
            throw new \Exception('Evaluación no encontrada');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($evaluacion, $validated) {
            // Recalcular IMC si se actualizan peso o estatura
            if (isset($validated['peso']) || isset($validated['estatura'])) {
                $peso = $validated['peso'] ?? $evaluacion->peso;
                $estatura = $validated['estatura'] ?? $evaluacion->estatura;
                if ($peso && $estatura && $estatura > 0) {
                    $validated['imc'] = $this->calcularIMC($peso, $estatura);
                }
            }

            // Recalcular composición corporal si se actualiza peso
            if (isset($validated['peso'])) {
                $composicion = $this->calcularComposicionCorporal(array_merge($evaluacion->toArray(), $validated));
                $validated = array_merge($validated, $composicion);
            }

            $evaluacion->update($validated);
            return $evaluacion->fresh(['cliente', 'nutricionista', 'evaluadoPor']);
        });
    }

    /**
     * Eliminar una evaluación
     */
    public function delete(int $id): bool
    {
        $evaluacion = $this->find($id);

        if (!$evaluacion) {
            throw new \Exception('Evaluación no encontrada');
        }

        // Verificar si tiene citas asociadas
        if ($evaluacion->citas()->exists()) {
            throw new \Exception('No se puede eliminar la evaluación porque tiene citas asociadas.');
        }

        return DB::transaction(function () use ($evaluacion) {
            return $evaluacion->delete();
        });
    }

    /**
     * Obtener la última evaluación de un cliente
     */
    public function getUltimaEvaluacion(int $clienteId): ?EvaluacionMedidasNutricion
    {
        return EvaluacionMedidasNutricion::where('cliente_id', $clienteId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Calcular IMC
     */
    public function calcularIMC(float $peso, float $estatura): float
    {
        if ($estatura <= 0) {
            throw new \Exception('La estatura debe ser mayor a cero');
        }
        return round($peso / ($estatura * $estatura), 2);
    }

    /**
     * Calcular composición corporal si faltan datos
     */
    public function calcularComposicionCorporal(array $data): array
    {
        $peso = $data['peso'] ?? 0;
        $porcentajeGrasa = $data['porcentaje_grasa'] ?? 0;
        $porcentajeMusculo = $data['porcentaje_musculo'] ?? 0;

        $resultado = [];

        // Calcular masa grasa en kg si tenemos porcentaje
        if ($porcentajeGrasa > 0 && !isset($data['masa_grasa'])) {
            $resultado['masa_grasa'] = round(($peso * $porcentajeGrasa) / 100, 2);
        }

        // Calcular masa muscular en kg si tenemos porcentaje
        if ($porcentajeMusculo > 0 && !isset($data['masa_muscular'])) {
            $resultado['masa_muscular'] = round(($peso * $porcentajeMusculo) / 100, 2);
        }

        return $resultado;
    }

    /**
     * Validar datos de la evaluación
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $rules = [
            'cliente_id' => [$isUpdate ? 'sometimes' : 'required', 'exists:clientes,id'],
            'peso' => ['nullable', 'numeric', 'min:0', 'max:500'],
            'estatura' => ['nullable', 'numeric', 'min:0.5', 'max:3'],
            'imc' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'porcentaje_grasa' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'porcentaje_musculo' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'masa_muscular' => ['nullable', 'numeric', 'min:0'],
            'masa_grasa' => ['nullable', 'numeric', 'min:0'],
            'masa_osea' => ['nullable', 'numeric', 'min:0'],
            'masa_residual' => ['nullable', 'numeric', 'min:0'],
            'circunferencias' => ['nullable', 'array'],
            'presion_arterial' => ['nullable', 'string', 'max:20'],
            'frecuencia_cardiaca' => ['nullable', 'integer', 'min:0', 'max:300'],
            'objetivo' => ['nullable', 'string', 'max:255'],
            'nutricionista_id' => ['nullable', 'exists:users,id'],
            'fecha_proxima_evaluacion' => ['nullable', 'date'],
            'estado' => ['nullable', 'string', 'in:pendiente,completada,cancelada'],
            'observaciones' => ['nullable', 'string'],
            'evaluado_por' => [$isUpdate ? 'sometimes' : 'required', 'exists:users,id'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
