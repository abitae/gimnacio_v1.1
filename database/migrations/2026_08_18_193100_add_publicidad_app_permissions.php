<?php

use App\Support\PermissionCatalog;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $guard = (string) config('auth.defaults.guard');
        $names = [
            'publicidad_app.ver',
            'publicidad_app.crear',
            'publicidad_app.editar',
            'publicidad_app.eliminar',
        ];

        foreach ($names as $name) {
            Permission::findOrCreate($name, $guard);
        }

        $branchAdmin = Role::query()
            ->where('name', PermissionCatalog::BRANCH_ADMIN_ROLE_NAME)
            ->where('guard_name', $guard)
            ->first();

        $branchAdmin?->givePermissionTo($names);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $guard = (string) config('auth.defaults.guard');
        Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', [
                'publicidad_app.ver',
                'publicidad_app.crear',
                'publicidad_app.editar',
                'publicidad_app.eliminar',
            ])
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
