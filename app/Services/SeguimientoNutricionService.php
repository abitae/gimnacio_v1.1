<?php

namespace App\Services;

use App\Models\Core\SeguimientoNutricion;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SeguimientoNutricionService
{
    public function getByCliente(int $clienteId, array $filtros = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = SeguimientoNutricion::query()
            ->with(['cliente', 'nutricionista', 'cita'])
            ->where('cliente_id', $clienteId)
            ->orderBy('fecha', 'desc');

        if (! empty($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }
        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (! empty($filtros['nutricionista_id'])) {
            $query->where('nutricionista_id', $filtros['nutricionista_id']);
        }
        if (! empty($filtros['fecha_desde'])) {
            $query->where('fecha', '>=', $filtros['fecha_desde']);
        }
        if (! empty($filtros['fecha_hasta'])) {
            $query->where('fecha', '<=', $filtros['fecha_hasta']);
        }

        return $query->paginate($perPage);
    }

    public function find(int $id): ?SeguimientoNutricion
    {
        return SeguimientoNutricion::with(['cliente', 'nutricionista', 'cita'])->find($id);
    }

    public function create(array $data): SeguimientoNutricion
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            $seg = SeguimientoNutricion::create($validated);
            return $seg->fresh(['cliente', 'nutricionista', 'cita']);
        });
    }

    public function update(int $id, array $data): SeguimientoNutricion
    {
        $seg = $this->find($id);
        if (! $seg) {
            throw new \Exception('Seguimiento no encontrado');
        }
        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($seg, $validated) {
            $seg->update($validated);
            return $seg->fresh(['cliente', 'nutricionista', 'cita']);
        });
    }

    public function delete(int $id): bool
    {
        $seg = $this->find($id);
        if (! $seg) {
            throw new \Exception('Seguimiento no encontrado');
        }
        return DB::transaction(function () use ($seg) {
            return $seg->delete();
        });
    }

    protected function validate(array $data, ?int $id = null): array
    {
        $rules = [
            'cliente_id' => [$id ? 'sometimes' : 'required', 'exists:clientes,id'],
            'nutricionista_id' => ['nullable', 'exists:users,id'],
            'cita_id' => ['nullable', 'exists:citas,id'],
            'tipo' => ['required', 'in:plan_inicial,seguimiento,recomendacion,incidencia,experiencia'],
            'fecha' => ['required', 'date'],
            'objetivo' => ['nullable', 'string', 'max:255'],
            'calorias_objetivo' => ['nullable', 'integer', 'min:0'],
            'macros' => ['nullable', 'array'],
            'macros.proteina' => ['nullable', 'numeric', 'min:0'],
            'macros.grasa' => ['nullable', 'numeric', 'min:0'],
            'macros.carbohidratos' => ['nullable', 'numeric', 'min:0'],
            'contenido' => ['nullable', 'string'],
            'estado' => ['nullable', 'in:borrador,activo,archivado'],
        ];

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
        return $validator->validated();
    }
}
