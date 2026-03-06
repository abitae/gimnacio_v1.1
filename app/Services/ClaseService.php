<?php

namespace App\Services;

use App\Models\Core\Clase;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ClaseService
{
    /**
     * Obtener clases con paginación
     */
    public function obtenerClases(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = Clase::with(['instructor'])
            ->orderBy('nombre');

        if (isset($filtros['busqueda'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('nombre', 'like', "%{$filtros['busqueda']}%")
                    ->orWhere('codigo', 'like', "%{$filtros['busqueda']}%");
            });
        }

        if (isset($filtros['tipo'])) {
            $query->where('tipo', $filtros['tipo']);
        }

        if (isset($filtros['instructor_id'])) {
            $query->where('instructor_id', $filtros['instructor_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Buscar clases para POS
     */
    public function buscarParaPOS(string $termino, int $limite = 20): Collection
    {
        return Clase::where('estado', 'activo')
            ->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('codigo', 'like', "%{$termino}%");
            })
            ->limit($limite)
            ->get();
    }

    /**
     * Obtener clase por ID
     */
    public function find(int $id): ?Clase
    {
        return Clase::with(['instructor'])->find($id);
    }

    /**
     * Crear clase
     */
    public function create(array $data): Clase
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            return Clase::create($validated);
        });
    }

    /**
     * Actualizar clase
     */
    public function update(int $id, array $data): Clase
    {
        $clase = $this->find($id);

        if (!$clase) {
            throw new \Exception('Clase no encontrada');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($clase, $validated) {
            $clase->update($validated);
            return $clase->fresh(['instructor']);
        });
    }

    /**
     * Eliminar clase
     */
    public function delete(int $id): bool
    {
        $clase = $this->find($id);

        if (!$clase) {
            throw new \Exception('Clase no encontrada');
        }

        return DB::transaction(function () use ($clase) {
            return $clase->delete();
        });
    }

    /**
     * Validar datos
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $rules = [
            'codigo' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50', 'unique:clases,codigo' . ($id ? ",{$id}" : '')],
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'tipo' => [$isUpdate ? 'sometimes' : 'required', 'string', 'in:sesion,paquete'],
            'precio_sesion' => ['nullable', 'required_if:tipo,sesion', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'precio_paquete' => ['nullable', 'required_if:tipo,paquete', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'sesiones_paquete' => ['nullable', 'required_if:tipo,paquete', 'integer', 'min:1'],
            'instructor_id' => ['nullable', 'exists:users,id'],
            'estado' => ['nullable', 'string', 'in:activo,inactivo'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
