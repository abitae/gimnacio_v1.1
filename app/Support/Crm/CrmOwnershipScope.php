<?php

namespace App\Support\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

class CrmOwnershipScope
{
    public static function canViewAll(?User $user = null): bool
    {
        $user ??= auth()->user();

        return $user !== null && $user->can('crm.ver_todos');
    }

    /**
     * Restringe la consulta a los registros del usuario autenticado (columna $column) o sin asignar,
     * salvo que pueda ver la cartera completa (permiso crm.ver_todos).
     */
    public static function restrictToOwner(Builder|Relation $query, string $column): Builder|Relation
    {
        if (! auth()->check() || self::canViewAll()) {
            return $query;
        }

        $userId = auth()->id();

        return $query->where(function (Builder $q) use ($column, $userId) {
            $q->where($column, $userId)->orWhereNull($column);
        });
    }

    /**
     * Variante para CrmActivity, que no tiene columna propia de ownership:
     * el dueño se deriva del lead, cliente o deal asociado (o queda visible si ninguno tiene dueño).
     */
    public static function restrictActivities(Builder|Relation $query): Builder|Relation
    {
        if (! auth()->check() || self::canViewAll()) {
            return $query;
        }

        $userId = auth()->id();

        return $query->where(function (Builder $q) use ($userId) {
            $q->whereHas('lead', fn (Builder $l) => $l->where('assigned_to', $userId)->orWhereNull('assigned_to'))
                ->orWhereHas('cliente', fn (Builder $c) => $c->where('asesor_crm_id', $userId)->orWhereNull('asesor_crm_id'))
                ->orWhereHas('deal', fn (Builder $d) => $d->where('assigned_to', $userId)->orWhereNull('assigned_to'))
                ->orWhere(function (Builder $q2) {
                    $q2->whereDoesntHave('lead')->whereDoesntHave('cliente')->whereDoesntHave('deal');
                });
        });
    }
}
