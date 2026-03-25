<?php

namespace App\Support;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Fuente de verdad para permisos CRUD por recurso y extras (Spatie).
 * Mantener alineado con middleware permission:, authorize() y @can en la app.
 */
final class PermissionCatalog
{
    public const SUPER_ADMIN_ROLE_NAME = 'super-admin';

    /** @deprecated Migración desde instalaciones anteriores; no usar en código nuevo. */
    public const LEGACY_SUPER_ADMIN_ROLE_NAME = 'super_administrador';

    public const CRUD_ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Recursos con permisos {recurso}.{view|create|update|delete}.
     *
     * @var list<string>
     */
    public const RESOURCES = [
        'clientes',
        'ejercicios-rutinas',
        'membresias',
        'cliente-matriculas',
        'clases',
        'cajas',
        'checking',
        'pos',
        'cupones',
        'payment-methods',
        'categorias-productos',
        'productos',
        'servicios',
        'gestion-nutricional',
        'crm-mensajes',
        'crm',
        'usuarios',
        'roles',
        'biotime',
        'reportes',
        'rentals',
        'employees',
        'attendance',
    ];

    /**
     * Permisos que no siguen el patrón CRUD del recurso.
     *
     * @var list<string>
     */
    public const EXTRA_PERMISSIONS = [
        'cajas.movimientos-manuales',
    ];

    /**
     * Permisos obsoletos que se eliminan al sincronizar.
     *
     * @var list<string>
     */
    public const LEGACY_PERMISSION_NAMES = [
        'manage-users',
        'manage-roles',
        'cliente-membresias.view',
        'cliente-membresias.create',
        'cliente-membresias.update',
        'cliente-membresias.delete',
    ];

    /**
     * Crea permisos en BD y devuelve la lista completa (extras + CRUD), en el mismo orden que RoleSeeder.
     *
     * @return list<string>
     */
    public static function sync(?string $guard = null): array
    {
        $guard ??= config('auth.defaults.guard');

        Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', self::LEGACY_PERMISSION_NAMES)
            ->delete();

        $allCrudPermissions = [];
        foreach (self::RESOURCES as $resource) {
            foreach (self::CRUD_ACTIONS as $action) {
                $name = "{$resource}.{$action}";
                Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
                $allCrudPermissions[] = $name;
            }
        }

        foreach (self::EXTRA_PERMISSIONS as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
        }

        return array_merge(self::EXTRA_PERMISSIONS, $allCrudPermissions);
    }

    /**
     * Migra usuarios del rol legacy al rol super-admin y elimina el rol antiguo.
     */
    public static function migrateLegacySuperAdminRole(?string $guard = null): void
    {
        $guard ??= config('auth.defaults.guard');

        $old = Role::query()
            ->where('name', self::LEGACY_SUPER_ADMIN_ROLE_NAME)
            ->where('guard_name', $guard)
            ->first();

        if (! $old) {
            return;
        }

        $new = Role::firstOrCreate(
            ['name' => self::SUPER_ADMIN_ROLE_NAME, 'guard_name' => $guard]
        );

        foreach ($old->users()->get() as $user) {
            if (! $user->hasRole(self::SUPER_ADMIN_ROLE_NAME)) {
                $user->assignRole($new);
            }
            $user->removeRole($old);
        }

        $old->delete();
    }
}
