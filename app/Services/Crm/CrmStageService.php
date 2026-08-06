<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmStage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CrmStageService
{
    public function listForManage(): Collection
    {
        return CrmStage::query()
            ->withCount('leads')
            ->orderBy('orden')
            ->get();
    }

    /**
     * @param  array{nombre: string, is_default?: bool, is_won?: bool, is_lost?: bool}  $data
     */
    public function create(array $data): CrmStage
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($payload) {
            $this->assertUniqueNombre($payload['nombre']);

            $maxOrden = (int) CrmStage::query()->max('orden');
            $payload['orden'] = $maxOrden + 1;

            if ($payload['is_default']) {
                $this->clearDefaults();
            } elseif (! CrmStage::query()->where('is_default', true)->exists()) {
                $payload['is_default'] = true;
            }

            return CrmStage::create($payload);
        });
    }

    /**
     * @param  array{nombre: string, is_default?: bool, is_won?: bool, is_lost?: bool}  $data
     */
    public function update(CrmStage $stage, array $data): CrmStage
    {
        $payload = $this->normalizePayload($data);

        return DB::transaction(function () use ($stage, $payload) {
            $this->assertUniqueNombre($payload['nombre'], $stage->id);

            if ($stage->is_default && ! $payload['is_default']) {
                $otherDefault = CrmStage::query()
                    ->where('id', '!=', $stage->id)
                    ->where('is_default', true)
                    ->exists();

                if (! $otherDefault) {
                    throw new InvalidArgumentException('Debe existir al menos una etapa por defecto.');
                }
            }

            if ($payload['is_default']) {
                $this->clearDefaults($stage->id);
            }

            $stage->update($payload);

            return $stage->fresh();
        });
    }

    public function delete(CrmStage $stage): void
    {
        DB::transaction(function () use ($stage) {
            $stage->loadCount('leads');

            if ($stage->leads_count > 0) {
                throw new InvalidArgumentException('No se puede eliminar una etapa con leads. Mueve o elimina los leads primero.');
            }

            if ($stage->is_default) {
                $replacement = CrmStage::query()
                    ->where('id', '!=', $stage->id)
                    ->orderBy('orden')
                    ->first();

                if (! $replacement) {
                    throw new InvalidArgumentException('No se puede eliminar la única etapa del pipeline.');
                }

                $replacement->update(['is_default' => true]);
            }

            $stage->delete();
            $this->resequenceOrden();
        });
    }

    public function moveUp(CrmStage $stage): void
    {
        DB::transaction(function () use ($stage) {
            $previous = CrmStage::query()
                ->where('orden', '<', $stage->orden)
                ->orderByDesc('orden')
                ->first();

            if (! $previous) {
                return;
            }

            $this->swapOrden($stage, $previous);
        });
    }

    public function moveDown(CrmStage $stage): void
    {
        DB::transaction(function () use ($stage) {
            $next = CrmStage::query()
                ->where('orden', '>', $stage->orden)
                ->orderBy('orden')
                ->first();

            if (! $next) {
                return;
            }

            $this->swapOrden($stage, $next);
        });
    }

    /**
     * @param  array{nombre: string, is_default?: bool, is_won?: bool, is_lost?: bool}  $data
     * @return array{nombre: string, is_default: bool, is_won: bool, is_lost: bool}
     */
    private function normalizePayload(array $data): array
    {
        $nombre = trim((string) ($data['nombre'] ?? ''));
        if ($nombre === '') {
            throw new InvalidArgumentException('El nombre de la etapa es obligatorio.');
        }

        if (mb_strlen($nombre) > 80) {
            throw new InvalidArgumentException('El nombre no puede superar 80 caracteres.');
        }

        $isWon = (bool) ($data['is_won'] ?? false);
        $isLost = (bool) ($data['is_lost'] ?? false);

        if ($isWon && $isLost) {
            throw new InvalidArgumentException('Una etapa no puede ser ganado y perdido a la vez.');
        }

        return [
            'nombre' => $nombre,
            'is_default' => (bool) ($data['is_default'] ?? false),
            'is_won' => $isWon,
            'is_lost' => $isLost,
        ];
    }

    private function assertUniqueNombre(string $nombre, ?int $ignoreId = null): void
    {
        $query = CrmStage::query()->where('nombre', $nombre);
        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new InvalidArgumentException('Ya existe una etapa con ese nombre.');
        }
    }

    private function clearDefaults(?int $exceptId = null): void
    {
        $query = CrmStage::query()->where('is_default', true);
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        $query->update(['is_default' => false]);
    }

    private function swapOrden(CrmStage $a, CrmStage $b): void
    {
        $ordenA = $a->orden;
        $a->update(['orden' => $b->orden]);
        $b->update(['orden' => $ordenA]);
    }

    private function resequenceOrden(): void
    {
        $stages = CrmStage::query()->orderBy('orden')->orderBy('id')->get();
        foreach ($stages as $index => $stage) {
            $expected = $index + 1;
            if ((int) $stage->orden !== $expected) {
                $stage->update(['orden' => $expected]);
            }
        }
    }
}
