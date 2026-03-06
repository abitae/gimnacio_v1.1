<?php

namespace App\Services;

use App\Models\Core\Producto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductoService
{
    /**
     * Obtener productos con paginación
     */
    public function obtenerProductos(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = Producto::with(['categoria'])
            ->orderBy('nombre');

        if (isset($filtros['busqueda'])) {
            $query->where(function ($q) use ($filtros) {
                $q->where('nombre', 'like', "%{$filtros['busqueda']}%")
                    ->orWhere('codigo', 'like', "%{$filtros['busqueda']}%");
            });
        }

        if (isset($filtros['categoria_id'])) {
            $query->where('categoria_id', $filtros['categoria_id']);
        }

        if (isset($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }

        if (isset($filtros['stock_bajo']) && $filtros['stock_bajo']) {
            $query->whereRaw('stock_actual <= stock_minimo');
        }

        return $query->paginate($perPage);
    }

    /**
     * Buscar productos para POS (búsqueda rápida)
     */
    public function buscarParaPOS(string $termino, int $limite = 20): Collection
    {
        return Producto::with(['categoria'])
            ->where('estado', 'activo')
            ->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('codigo', 'like', "%{$termino}%");
            })
            ->where('stock_actual', '>', 0)
            ->limit($limite)
            ->get();
    }

    /**
     * Obtener producto por ID
     */
    public function find(int $id): ?Producto
    {
        return Producto::with(['categoria'])->find($id);
    }

    /**
     * Crear producto
     */
    public function create(array $data): Producto
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            return Producto::create($validated);
        });
    }

    /**
     * Actualizar producto
     */
    public function update(int $id, array $data): Producto
    {
        $producto = $this->find($id);

        if (!$producto) {
            throw new \Exception('Producto no encontrado');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($producto, $validated) {
            $producto->update($validated);
            return $producto->fresh(['categoria']);
        });
    }

    /**
     * Eliminar producto
     */
    public function delete(int $id): bool
    {
        $producto = $this->find($id);

        if (!$producto) {
            throw new \Exception('Producto no encontrado');
        }

        return DB::transaction(function () use ($producto) {
            return $producto->delete();
        });
    }

    /**
     * Validar datos
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $rules = [
            'codigo' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50', 'unique:productos,codigo' . ($id ? ",{$id}" : '')],
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias_productos,id'],
            'precio_venta' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'precio_compra' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'stock_actual' => ['nullable', 'integer', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'unidad_medida' => ['nullable', 'string', 'max:20'],
            'imagen' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'in:activo,inactivo'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
