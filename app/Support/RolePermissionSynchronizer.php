<?php

namespace App\Support;

use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolePermissionSynchronizer
{
    /**
     * Sincroniza permisos y asignaciones de roles desde PermissionCatalog.
     *
     * @return array{permissions: int, roles: int}
     */
    public static function sync(?string $guard = null): array
    {
        $guard ??= (string) config('auth.defaults.guard');

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::roleNames() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
        }

        $syncedPermissions = PermissionCatalog::sync($guard);
        PermissionCatalog::migrateLegacyRoles($guard);

        foreach (PermissionCatalog::roleDefinitions() as $roleName => $definition) {
            $role = Role::findByName($roleName, $guard);
            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return [
            'permissions' => count($syncedPermissions),
            'roles' => count(PermissionCatalog::roleDefinitions()),
        ];
    }
}
