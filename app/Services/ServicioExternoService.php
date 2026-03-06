<?php

namespace App\Services;

use App\Models\Core\ServicioExterno;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ServicioExternoService
{
    /**
     * Obtener servicios con paginación
     */
    public function obtenerServicios(int $perPage = 15, array $filtros = []): LengthAwarePaginator
    {
        $query = ServicioExterno::with(['categoria'])
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

        return $query->paginate($perPage);
    }

    /**
     * Buscar servicios para POS
     */
    public function buscarParaPOS(string $termino, int $limite = 20): Collection
    {
        return ServicioExterno::where('estado', 'activo')
            ->where(function ($q) use ($termino) {
                $q->where('nombre', 'like', "%{$termino}%")
                    ->orWhere('codigo', 'like', "%{$termino}%");
            })
            ->limit($limite)
            ->get();
    }

    /**
     * Obtener servicio por ID
     */
    public function find(int $id): ?ServicioExterno
    {
        return ServicioExterno::with(['categoria'])->find($id);
    }

    /**
     * Crear servicio
     */
    public function create(array $data): ServicioExterno
    {
        $validated = $this->validate($data);

        return DB::transaction(function () use ($validated) {
            return ServicioExterno::create($validated);
        });
    }

    /**
     * Actualizar servicio
     */
    public function update(int $id, array $data): ServicioExterno
    {
        $servicio = $this->find($id);

        if (!$servicio) {
            throw new \Exception('Servicio no encontrado');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($servicio, $validated) {
            $servicio->update($validated);
            return $servicio->fresh(['categoria']);
        });
    }

    /**
     * Eliminar servicio
     */
    public function delete(int $id): bool
    {
        $servicio = $this->find($id);

        if (!$servicio) {
            throw new \Exception('Servicio no encontrado');
        }

        return DB::transaction(function () use ($servicio) {
            return $servicio->delete();
        });
    }

    /**
     * Validar datos
     */
    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $rules = [
            'codigo' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:50', 'unique:servicios_externos,codigo' . ($id ? ",{$id}" : '')],
            'nombre' => [$isUpdate ? 'sometimes' : 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'categoria_id' => ['nullable', 'exists:categorias_servicios,id'],
            'precio' => [$isUpdate ? 'sometimes' : 'required', 'numeric', 'min:0', 'regex:/^\d+(\.\d{1,2})?$/'],
            'duracion_minutos' => ['nullable', 'integer', 'min:1'],
            'estado' => ['nullable', 'string', 'in:activo,inactivo'],
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }
}
