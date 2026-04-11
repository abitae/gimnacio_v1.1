<?php

namespace App\Services;

use App\Models\System\Sucursal;
use App\Models\User;
use App\Support\PermissionCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Session;

class SucursalContext
{
    public const EMPRESA_ID_KEY = 'empresa_activa_id';

    public const SUCURSAL_ID_KEY = 'sucursal_activa_id';

    public const SUCURSAL_NOMBRE_KEY = 'sucursal_activa_nombre';

    protected ?Sucursal $resolvedSucursal = null;

    /** Contexto explícito (p. ej. jobs en cola sin sesión HTTP). */
    protected ?int $delegateSucursalId = null;

    protected ?int $delegateEmpresaId = null;

    /**
     * Fija sucursal/empresa para consultas con alcance global (jobs, comandos).
     * Llamar a clearDelegateContext() al finalizar el trabajo.
     */
    public function setDelegateContext(?int $sucursalId, ?int $empresaId = null): void
    {
        $this->delegateSucursalId = $sucursalId;
        $this->delegateEmpresaId = $empresaId;
        $this->resolvedSucursal = null;
    }

    public function clearDelegateContext(): void
    {
        $this->delegateSucursalId = null;
        $this->delegateEmpresaId = null;
        $this->resolvedSucursal = null;
    }

    public function getSucursalId(): ?int
    {
        if ($this->delegateSucursalId !== null) {
            return $this->delegateSucursalId;
        }

        $value = Session::get(self::SUCURSAL_ID_KEY);

        return $value !== null ? (int) $value : null;
    }

    public function getEmpresaId(): ?int
    {
        if ($this->delegateEmpresaId !== null) {
            return $this->delegateEmpresaId;
        }

        $value = Session::get(self::EMPRESA_ID_KEY);

        return $value !== null ? (int) $value : null;
    }

    public function getSucursalNombre(): ?string
    {
        return Session::get(self::SUCURSAL_NOMBRE_KEY);
    }

    public function hasActiveSucursal(): bool
    {
        return $this->getSucursalId() !== null;
    }

    public function sucursal(): ?Sucursal
    {
        $sucursalId = $this->getSucursalId();

        if ($sucursalId === null) {
            return null;
        }

        if ($this->resolvedSucursal?->id === $sucursalId) {
            return $this->resolvedSucursal;
        }

        $this->resolvedSucursal = Sucursal::query()
            ->with('empresa')
            ->find($sucursalId);

        return $this->resolvedSucursal;
    }

    public function activate(Sucursal $sucursal): void
    {
        Session::put([
            self::EMPRESA_ID_KEY => $sucursal->empresa_id,
            self::SUCURSAL_ID_KEY => $sucursal->id,
            self::SUCURSAL_NOMBRE_KEY => $sucursal->nombre,
        ]);

        $this->resolvedSucursal = $sucursal;
    }

    public function clear(): void
    {
        Session::forget([
            self::EMPRESA_ID_KEY,
            self::SUCURSAL_ID_KEY,
            self::SUCURSAL_NOMBRE_KEY,
        ]);

        $this->resolvedSucursal = null;
    }

    public function availableForUser(User $user)
    {
        return $this->branchesQueryForUser($user)
            ->orderByDesc('es_principal')
            ->orderBy('nombre')
            ->get();
    }

    public function resolveForUser(User $user, ?int $sucursalId = null): ?Sucursal
    {
        $query = $this->branchesQueryForUser($user);

        if ($sucursalId !== null) {
            return $query->whereKey($sucursalId)->first();
        }

        if ($user->default_sucursal_id !== null) {
            $defaultSucursal = (clone $query)->whereKey($user->default_sucursal_id)->first();

            if ($defaultSucursal) {
                return $defaultSucursal;
            }
        }

        return $query
            ->orderByDesc('es_principal')
            ->orderBy('nombre')
            ->first();
    }

    protected function branchesQueryForUser(User $user): Builder|BelongsToMany
    {
        $baseQuery = Sucursal::query()
            ->with('empresa')
            ->where('sucursales.estado', 'activa')
            ->whereHas('empresa', fn ($builder) => $builder->where('estado', 'activa'));

        if (method_exists($user, 'hasRole') && $user->hasRole(PermissionCatalog::SUPER_ADMIN_ROLE_NAME)) {
            return $baseQuery;
        }

        return $user->sucursales()
            ->with('empresa')
            ->where('sucursales.estado', 'activa')
            ->whereHas('empresa', fn ($builder) => $builder->where('estado', 'activa'));
    }

    public function getFallbackSucursalId(): ?int
    {
        $fromSessionOrDelegate = $this->getSucursalId();

        if ($fromSessionOrDelegate !== null) {
            return $fromSessionOrDelegate;
        }

        if (! app()->runningInConsole()) {
            return null;
        }

        return Sucursal::query()
            ->where('estado', 'activa')
            ->orderByDesc('es_principal')
            ->value('id');
    }
}
