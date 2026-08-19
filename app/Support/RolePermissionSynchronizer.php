<?php

namespace App\Support;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSynchronizer
{
    /**
     * Asegura permisos y roles del catálogo.
     *
     * Por defecto no pisa la configuración de producción: solo crea lo faltante.
     * Con $reset = true, alinea roles existentes al catálogo (syncPermissions + migración legacy).
     *
     * @return array{permissions: int, roles: int, created_roles: list<string>, reset: bool}
     */
    public static function sync(?string $guard = null, bool $reset = false): array
    {
        $guard ??= (string) config('auth.defaults.guard');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $createdRoleNames = [];
        foreach (PermissionCatalog::roleNames() as $roleName) {
            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guard,
            ]);

            if ($role->wasRecentlyCreated) {
                $createdRoleNames[] = $roleName;
            }
        }

        $syncedPermissions = PermissionCatalog::sync($guard, deleteLegacy: $reset);

        if ($reset) {
            PermissionCatalog::migrateLegacyRoles($guard);
        }

        foreach (PermissionCatalog::roleDefinitions() as $roleName => $definition) {
            $isNewRole = in_array($roleName, $createdRoleNames, true);
            if (! $reset && ! $isNewRole) {
                continue;
            }

            $role = Role::findByName($roleName, $guard);
            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'permissions' => count($syncedPermissions),
            'roles' => count(PermissionCatalog::roleDefinitions()),
            'created_roles' => $createdRoleNames,
            'reset' => $reset,
        ];
    }
}
