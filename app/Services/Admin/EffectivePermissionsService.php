<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Collection;

class EffectivePermissionsService
{
    /**
     * @return array{roles: Collection, permissions: array<string, list<string>>, is_super_admin: bool}
     */
    public function forUser(User $user): array
    {
        $roles = $user->roles()->orderBy('name')->get(['id', 'name']);
        $permissions = $user->getAllPermissions()
            ->pluck('name')
            ->sort()
            ->values();

        $grouped = [];
        foreach ($permissions as $permission) {
            $parts = explode('.', (string) $permission, 2);
            $module = $parts[0] ?? 'general';
            $grouped[$module] ??= [];
            $grouped[$module][] = (string) $permission;
        }
        ksort($grouped);

        return [
            'roles' => $roles,
            'permissions' => $grouped,
            'is_super_admin' => $user->hasRole('super_administrador'),
        ];
    }

    public function explains(string $permission, User $user): ?string
    {
        if ($user->can($permission)) {
            if ($user->hasRole('super_administrador')) {
                return 'Rol super_administrador';
            }

            $via = $user->getDirectPermissions()->contains('name', $permission)
                ? 'Permiso directo'
                : 'Heredado de rol: '.($user->roles->first(fn ($r) => $r->hasPermissionTo($permission))?->name ?? '—');

            return $via;
        }

        return null;
    }
}
