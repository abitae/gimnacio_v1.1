<?php

namespace App\Services;

use App\Models\Core\CategoriaProducto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriaProductoService
{
    /**
     * Obtener categorías con paginación
     */
    public function obtenerCategorias(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = CategoriaProducto::withCount('productos')
            ->orderBy('nombre');

        if (isset($filtros['busqueda'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('nombre', 'like', "%{$filtros['busqueda']}%")
                    ->orWhere('descripcion', 'like', "%{$filtros['busqueda']}%");
            });
        }

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Obtener categoría por ID
     */
    public function find(int $id): ?CategoriaProducto
    {
        return CategoriaProducto::find($id);
    }

    /**
     * Crear categoría
     */
    public function create(array $data): CategoriaProducto
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            return CategoriaProducto::create($validated);
        });
    }

    /**
     * Actualizar categoría
     */
    public function update(int $id, array $data): CategoriaProducto
    {
        $categoria = $this->find($id);

        if (!$categoria) {
            throw new \Exception('Categoría no encontrada');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($categoria, $validated) {
            $categoria->update($validated);
            return $categoria->fresh();
        });
    }

    /**
     * Eliminar categoría
     */
    public function delete(int $id): bool
    {
        $categoria = $this->find($id);

        if (!$categoria) {
            throw new \Exception('Categoría no encontrada');
        }

        // Validar que no tenga productos asociados
        if ($categoria->productos()->count() > 0) {
            throw new \Exception('No se puede eliminar la categoría porque tiene productos asociados.');
        }

        return DB::transaction(function () use ($categoria) {
            return $categoria->delete();
        });
    }

    /**
     * Validar datos
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $rules = [
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'in:activa,inactiva'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
