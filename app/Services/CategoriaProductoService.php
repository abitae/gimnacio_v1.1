<?php

namespace App\Services;

use App\Models\Core\CategoriaProducto;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoriaProductoService
{
    public function __construct(
        protected SucursalContext $sucursalContext
    ) {}

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
        $validated['sucursal_id'] = $validated['sucursal_id'] ?? $this->sucursalContext->getFallbackSucursalId();

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
            $validated['sucursal_id'] = $categoria->sucursal_id;
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
            'nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) use ($data, $id) {
                    $sucursalId = $this->resolveSucursalId($data, $id);
                    if ($sucursalId <= 0) {
                        return;
                    }

                    $exists = CategoriaProducto::query()
                        ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim((string) $value))])
                        ->where('sucursal_id', $sucursalId)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe una categoria con ese nombre en la sucursal.');
                    }
                },
            ],
            'descripcion' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'in:activa,inactiva'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    private function resolveSucursalId(array $data, ?int $id): int
    {
        if (! empty($data['sucursal_id'])) {
            return (int) $data['sucursal_id'];
        }

        if ($id !== null) {
            return (int) (CategoriaProducto::query()->whereKey($id)->value('sucursal_id') ?? 0);
        }

        return (int) ($this->sucursalContext->getFallbackSucursalId() ?? 0);
    }
}
