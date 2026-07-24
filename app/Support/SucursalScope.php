<?php

namespace App\Support;

use App\Models\User;
use App\Services\SucursalContext;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class SucursalScope
{
    public function __construct(
        protected SucursalContext $context
    ) {}

    public function activeSucursalId(): ?int
    {
        return $this->context->getSucursalId();
    }

    public function assertActiveSucursal(): int
    {
        $id = $this->context->getSucursalId();

        if ($id === null) {
            abort(403, 'No hay sucursal activa en la sesión.');
        }

        return $id;
    }

    public function assertRecordBelongsToActiveSucursal(Model $model): void
    {
        if (! $model->getAttribute('sucursal_id')) {
            return;
        }

        $activeId = $this->context->getSucursalId();

        if ($activeId !== null && (int) $model->getAttribute('sucursal_id') !== (int) $activeId) {
            abort(404);
        }
    }

    /**
     * @param  callable(): mixed  $callback
     */
    public function runForSucursal(int $sucursalId, ?int $empresaId, callable $callback): mixed
    {
        $this->context->setDelegateContext($sucursalId, $empresaId);

        try {
            return $callback();
        } finally {
            $this->context->clearDelegateContext();
        }
    }

    /**
     * @param  array<int>  $sucursalIds
     * @param  callable(Builder): Builder  $scopeCallback
     */
    public function applyReportScope(Builder $query, ?int $specificSucursalId = null, bool $consolidated = false, ?callable $scopeCallback = null): Builder
    {
        $user = Auth::user();

        if ($consolidated && $user instanceof User && $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
            $allowedIds = $this->context->availableForUser($user)->pluck('id')->all();

            return $query
                ->withoutGlobalScope('active_sucursal')
                ->whereIn($query->qualifyColumn('sucursal_id'), $allowedIds);
        }

        if ($specificSucursalId !== null && $user instanceof User && $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
            $allowed = $this->context->availableForUser($user)->contains('id', $specificSucursalId);

            if ($allowed) {
                return $query
                    ->withoutGlobalScope('active_sucursal')
                    ->where($query->qualifyColumn('sucursal_id'), $specificSucursalId);
            }
        }

        if ($scopeCallback) {
            return $scopeCallback($query);
        }

        return $query;
    }
}
