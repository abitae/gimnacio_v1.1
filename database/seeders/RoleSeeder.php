<?php

namespace Database\Seeders;

use App\Support\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = config('auth.defaults.guard');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $roles = [
            PermissionCatalog::SUPER_ADMIN_ROLE_NAME,
            'administrador',
            'trainer',
            'caja',
            'vendedor',
            'cafetin',
            'nutricionista',
        ];

        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => $guard]);
        }

        $activePermissions = PermissionCatalog::sync($guard);
        PermissionCatalog::migrateLegacySuperAdminRole($guard);

        $superAdmin = Role::findByName(PermissionCatalog::SUPER_ADMIN_ROLE_NAME, $guard);
        $superAdmin->syncPermissions([]);

        $admin = Role::findByName('administrador', $guard);
        $admin->syncPermissions($activePermissions);

        $trainer = Role::findByName('trainer', $guard);
        $trainer->syncPermissions([
            'clientes.view', 'clientes.update',
            'clases.view',
            'cliente-matriculas.view',
            'gestion-nutricional.view', 'gestion-nutricional.create', 'gestion-nutricional.update', 'gestion-nutricional.delete',
            'checking.view', 'checking.create', 'checking.update',
            'ejercicios-rutinas.view', 'ejercicios-rutinas.create', 'ejercicios-rutinas.update', 'ejercicios-rutinas.delete',
        ]);

        $caja = Role::findByName('caja', $guard);
        $caja->syncPermissions([
            'cajas.view', 'cajas.create', 'cajas.update',
            'cajas.movimientos-manuales',
            'pos.view', 'pos.create',
            'reportes.view',
            'clientes.view', 'cliente-matriculas.view', 'cliente-matriculas.update',
            'payment-methods.view',
            'rentals.view', 'rentals.create', 'rentals.update',
            'employees.view', 'attendance.view', 'attendance.create',
        ]);

        $vendedor = Role::findByName('vendedor', $guard);
        $vendedor->syncPermissions([
            'pos.view', 'pos.create',
            'clientes.view',
            'membresias.view',
            'clases.view',
            'cliente-matriculas.view', 'cliente-matriculas.create', 'cliente-matriculas.update',
            'crm.view', 'crm.create', 'crm.update',
            'crm-mensajes.view', 'crm-mensajes.create',
            'cupones.view',
            'productos.view',
            'categorias-productos.view',
        ]);

        $cafetin = Role::findByName('cafetin', $guard);
        $cafetin->syncPermissions([
            'pos.view', 'pos.create',
            'productos.view',
            'categorias-productos.view',
        ]);

        $nutricionista = Role::findByName('nutricionista', $guard);
        $nutricionista->syncPermissions([
            'gestion-nutricional.view', 'gestion-nutricional.create', 'gestion-nutricional.update', 'gestion-nutricional.delete',
            'clientes.view',
            'crm-mensajes.view', 'crm-mensajes.create',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
