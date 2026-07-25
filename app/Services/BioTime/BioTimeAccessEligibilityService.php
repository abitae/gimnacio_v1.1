<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteMatricula;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Fuente de verdad de acceso fisico BioTime: matricula vigente tipo membresia o clase.
 * Legacy cliente_membresias NO otorga acceso.
 */
class BioTimeAccessEligibilityService
{
    public function isEligible(Cliente $cliente, int $sucursalId): bool
    {
        if ((int) $cliente->sucursal_id !== $sucursalId) {
            return false;
        }

        return $this->eligibleMatriculaQuery($sucursalId, Carbon::today())
            ->where('cliente_id', $cliente->id)
            ->exists();
    }

    /**
     * @return Collection<int, int>
     */
    public function listEligibleClienteIds(int $sucursalId): Collection
    {
        return $this->eligibleMatriculaQuery($sucursalId, Carbon::today())
            ->whereIn(
                'cliente_id',
                Cliente::query()->where('sucursal_id', $sucursalId)->select('id')
            )
            ->distinct()
            ->pluck('cliente_id')
            ->map(fn ($id) => (int) $id)
            ->values();
    }

    /**
     * Elegibles ordenados por inicio de la matricula vigente mas reciente.
     *
     * @return Collection<int, array{cliente_id:int,priority_at:string,matricula_id:int,rank:int}>
     */
    public function prioritizedEligible(int $sucursalId): Collection
    {
        $rows = $this->eligibleMatriculaQuery($sucursalId, Carbon::today())
            ->select(['id', 'cliente_id', 'fecha_inicio'])
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->get()
            ->unique('cliente_id')
            ->sort(function (ClienteMatricula $a, ClienteMatricula $b): int {
                $dateComparison = strcmp(
                    (string) $b->fecha_inicio?->toDateString(),
                    (string) $a->fecha_inicio?->toDateString()
                );

                return $dateComparison !== 0
                    ? $dateComparison
                    : (((int) $b->id <=> (int) $a->id) ?: ((int) $b->cliente_id <=> (int) $a->cliente_id));
            })
            ->values();

        return $rows->map(fn (ClienteMatricula $matricula, int $index): array => [
            'cliente_id' => (int) $matricula->cliente_id,
            'priority_at' => $matricula->fecha_inicio?->toDateString() ?? '',
            'matricula_id' => (int) $matricula->id,
            'rank' => $index + 1,
        ]);
    }

    /**
     * Matricula vigente: tipo membresia o clase, estado activa, fechas vigentes, de la sucursal.
     *
     * @return \Illuminate\Database\Eloquent\Builder<ClienteMatricula>
     */
    public function eligibleMatriculaQuery(int $sucursalId, Carbon $today)
    {
        $date = $today->toDateString();

        return ClienteMatricula::query()
            ->whereIn('tipo', ['membresia', 'clase'])
            ->where('estado', 'activa')
            ->where('sucursal_id', $sucursalId)
            ->whereDate('fecha_inicio', '<=', $date)
            ->where(function ($query) use ($date): void {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', $date);
            });
    }
}
