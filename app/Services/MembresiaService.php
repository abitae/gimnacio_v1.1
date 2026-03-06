<?php

namespace App\Services;

use App\Models\Core\Membresia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class MembresiaService
{
    /**
     * Obtener todas las membresías con paginación
     */
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Membresia::query()
            ->select(['id', 'nombre', 'descripcion', 'duracion_dias', 'precio_base', 'tipo_acceso', 'max_visitas_dia', 'permite_congelacion', 'max_dias_congelacion', 'estado', 'created_at'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Buscar membresías por término de búsqueda
     */
    public function search(string $search, ?string $estado = null, int $perPage = 15): LengthAwarePaginator
    {
        $query = Membresia::query()
            ->select(['id', 'nombre', 'descripcion', 'duracion_dias', 'precio_base', 'tipo_acceso', 'max_visitas_dia', 'permite_congelacion', 'max_dias_congelacion', 'estado', 'created_at']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        if ($estado) {
            $query->where('estado', $estado);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Obtener una membresía por ID
     */
    public function find(int $id): ?Membresia
    {
        return Membresia::find($id);
    }

    /**
     * Crear una nueva membresía
     */
    public function create(array $data): Membresia
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            return Membresia::create($validated);
        });
    }

    /**
     * Actualizar una membresía
     */
    public function update(int $id, array $data): Membresia
    {
        $membresia = $this->find($id);

        if (!$membresia) {
            throw new \Exception('Membresía no encontrada');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($membresia, $validated) {
            $membresia->update($validated);
            return $membresia->fresh();
        });
    }

    /**
     * Eliminar una membresía
     */
    public function delete(int $id): bool
    {
        $membresia = $this->find($id);

        if (!$membresia) {
            throw new \Exception('Membresía no encontrada');
        }

        // Verificar si tiene relaciones
        $this->checkRelations($membresia);

        return DB::transaction(function () use ($membresia) {
            return $membresia->delete();
        });
    }

    /**
     * Validar datos de la membresía
     */
    protected function validate(array $data, ?int $id = null): array
    {
        // En actualizaciones, solo validar campos que están presentes
        $isUpdate = $id !== null;
        
        $rules = [
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string'],
            'duracion_dias' => [$isUpdate ? 'sometimes' : 'required', 'integer', 'min:1'],
            'precio_base' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'tipo_acceso' => ['nullable', 'string', 'in:ilimitado,limitado'],
            'max_visitas_dia' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($data) {
                    if (isset($data['tipo_acceso']) && $data['tipo_acceso'] === 'limitado' && empty($value)) {
                        $fail('El campo máximo de visitas por día es requerido cuando el tipo de acceso es limitado.');
                    }
                },
            ],
            'permite_congelacion' => ['nullable', 'boolean'],
            'max_dias_congelacion' => [
                'nullable',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) use ($data) {
                    if (isset($data['permite_congelacion']) && $data['permite_congelacion'] === true && empty($value)) {
                        $fail('El campo máximo de días de congelación es requerido cuando se permite congelación.');
                    }
                },
            ],
            'estado' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:activa,inactiva'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Verificar relaciones antes de eliminar
     */
    protected function checkRelations(Membresia $membresia): void
    {
        $hasClienteMembresias = $membresia->clienteMembresias()->exists();

        if ($hasClienteMembresias) {
            throw new \Exception('No se puede eliminar la membresía porque tiene clientes asociados.');
        }
    }
}
