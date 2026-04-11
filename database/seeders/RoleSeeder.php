<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('auth.defaults.guard');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionCatalog::roleNames() as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
        }

        PermissionCatalog::sync($guard);
        PermissionCatalog::migrateLegacyRoles($guard);

        foreach (PermissionCatalog::roleDefinitions() as $roleName => $definition) {
            $role = Role::findByName($roleName, $guard);
            $role->syncPermissions($definition['permissions']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
