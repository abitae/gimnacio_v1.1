<?php

namespace App\Services;

use App\Models\Core\RentableSpace;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RentableSpaceService
{
    public function __construct(
        protected SucursalContext $sucursalContext
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return RentableSpace::query()->orderBy('nombre')->paginate($perPage);
    }

    public function find(int $id): ?RentableSpace
    {
        return RentableSpace::query()->find($id);
    }

    public function create(array $data): RentableSpace
    {
        $validated = $this->validate($data);
        $validated['sucursal_id'] = $validated['sucursal_id'] ?? $this->sucursalContext->getFallbackSucursalId();

        return DB::transaction(fn () => RentableSpace::query()->create($validated));
    }

    public function update(int $id, array $data): RentableSpace
    {
        $space = $this->find($id);

        if (! $space) {
            throw new \InvalidArgumentException('Espacio no encontrado.');
        }

        $validated = $this->validate($data, $id);

        return DB::transaction(function () use ($space, $validated) {
            $validated['sucursal_id'] = $space->sucursal_id;
            $space->update($validated);

            return $space->fresh();
        });
    }

    public function toggleEstado(int $id): RentableSpace
    {
        $space = $this->find($id);

        if (! $space) {
            throw new \InvalidArgumentException('Espacio no encontrado.');
        }

        return DB::transaction(function () use ($space) {
            $space->update([
                'estado' => $space->estado === 'activo' ? 'inactivo' : 'activo',
            ]);

            return $space->fresh();
        });
    }

    protected function validate(array $data, ?int $id = null): array
    {
        $isUpdate = $id !== null;

        $validator = Validator::make($data, [
            'nombre' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:120',
                function ($attribute, $value, $fail) use ($data, $id) {
                    $sucursalId = $this->resolveSucursalId($data, $id);
                    if ($sucursalId <= 0) {
                        return;
                    }

                    $exists = RentableSpace::query()
                        ->whereRaw('LOWER(TRIM(nombre)) = ?', [mb_strtolower(trim((string) $value))])
                        ->where('sucursal_id', $sucursalId)
                        ->when($id, fn ($q) => $q->where('id', '!=', $id))
                        ->exists();

                    if ($exists) {
                        $fail('Ya existe un espacio con ese nombre en la sucursal.');
                    }
                },
            ],
            'tipo' => ['nullable', 'string', 'max:40'],
            'descripcion' => ['nullable', 'string'],
            'capacidad' => ['nullable', 'integer', 'min:0'],
            'estado' => ['required', 'in:activo,inactivo'],
            'color_calendario' => ['nullable', 'string', 'max:20'],
            'sucursal_id' => ['nullable', 'exists:sucursales,id'],
        ]);

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
            return (int) (RentableSpace::query()->whereKey($id)->value('sucursal_id') ?? 0);
        }

        return (int) ($this->sucursalContext->getFallbackSucursalId() ?? 0);
    }
}
