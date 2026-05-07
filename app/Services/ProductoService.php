<?php

namespace App\Services;

use App\Models\Core\Producto;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ProductoService
{
    public function __construct(
        protected SucursalContext $sucursalContext
    ) {}

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
        $validated['sucursal_id'] = $validated['sucursal_id'] ?? $this->sucursalContext->getFallbackSucursalId();
        $shouldGenerateCodigo = empty($validated['codigo']);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                return DB::transaction(function () use ($validated, $shouldGenerateCodigo) {
                    $payload = $validated;

                    if ($shouldGenerateCodigo) {
                        $payload['codigo'] = $this->generateNextCodigo();
                    }

                    return Producto::create($payload);
                });
            } catch (QueryException $e) {
                if (! $shouldGenerateCodigo || ! $this->isUniqueConstraintViolation($e) || $attempt === 2) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('No se pudo generar un codigo unico para el producto.');
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
            $validated['sucursal_id'] = $producto->sucursal_id;
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
        $data = $this->normalizeData($data);

        $rules = [
            'codigo' => [
                'sometimes',
                'string',
                'max:50',
                function ($attribute, $value, $fail) use ($data, $id) {
                    $sucursalId = $this->resolveSucursalId($data, $id, Producto::class);
                    if ($sucursalId <= 0) {
                        return;
                    }

                    $exists = Producto::query()
                        ->where('codigo', trim((string) $value))
                        ->where('sucursal_id', $sucursalId)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un producto con este codigo en la sucursal.');
                    }
                },
            ],
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) use ($data, $id) {
                    if (! $value) {
                        return;
                    }

                    $sucursalId = $this->resolveSucursalId($data, $id, Producto::class);
                    $exists = \App\Models\Core\CategoriaProducto::query()
                        ->whereKey($value)
                        ->where('sucursal_id', $sucursalId)
                        ->exists();

                    if (! $exists) {
                        $fail('La categoria seleccionada no pertenece a la sucursal activa.');
                    }
                },
            ],
            'precio_venta' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'precio_compra' => ['nullable', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'stock_actual' => ['nullable', 'integer', 'min:0'],
            'stock_minimo' => ['nullable', 'integer', 'min:0'],
            'unidad_medida' => ['nullable', 'string', 'max:20'],
            'imagen' => ['nullable', 'string'],
            'estado' => ['nullable', 'string', 'in:activo,inactivo'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    protected function normalizeData(array $data): array
    {
        if (array_key_exists('codigo', $data)) {
            $codigo = trim((string) $data['codigo']);

            if ($codigo === '') {
                unset($data['codigo']);
            } else {
                $data['codigo'] = $codigo;
            }
        }

        return $data;
    }

    protected function generateNextCodigo(): string
    {
        $codigos = Producto::withoutGlobalScopes()
            ->where('codigo', 'like', 'PROD-%')
            ->lockForUpdate()
            ->pluck('codigo');

        $maximo = 0;

        foreach ($codigos as $codigo) {
            if (preg_match('/^PROD-(\d+)$/', (string) $codigo, $matches) !== 1) {
                continue;
            }

            $maximo = max($maximo, (int) $matches[1]);
        }

        return 'PROD-'.str_pad((string) ($maximo + 1), 4, '0', STR_PAD_LEFT);
    }

    protected function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? $exception->getCode());
        $message = strtolower($exception->getMessage());

        return $sqlState === '23000'
            || str_contains($message, 'unique')
            || str_contains($message, 'duplicate');
    }

    private function resolveSucursalId(array $data, ?int $id, string $modelClass): int
    {
        if (! empty($data['sucursal_id'])) {
            return (int) $data['sucursal_id'];
        }

        if ($id !== null) {
            return (int) ($modelClass::query()->whereKey($id)->value('sucursal_id') ?? 0);
        }

        return (int) ($this->sucursalContext->getFallbackSucursalId() ?? 0);
    }
}
