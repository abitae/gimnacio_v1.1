<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
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
            'super_administrador',
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

        Permission::firstOrCreate(['name' => 'cajas.movimientos-manuales', 'guard_name' => $guard]);

        Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', [
                'manage-users',
                'manage-roles',
                'cliente-membresias.view',
                'cliente-membresias.create',
                'cliente-membresias.update',
                'cliente-membresias.delete',
            ])
            ->delete();

        $resources = [
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
        $actions = ['view', 'create', 'update', 'delete'];
        $allCrudPermissions = [];
        foreach ($resources as $resource) {
            foreach ($actions as $action) {
                $name = "{$resource}.{$action}";
                Permission::firstOrCreate(['name' => $name, 'guard_name' => $guard]);
                $allCrudPermissions[] = $name;
            }
        }

        $activePermissions = array_merge(['cajas.movimientos-manuales'], $allCrudPermissions);

        $superAdmin = Role::findByName('super_administrador', $guard);
        $superAdmin->syncPermissions($activePermissions);

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

        // Usuario inicial como super administrador
        $firstUser = User::where('email', 'abel.arana@hotmail.com')->first();
        if ($firstUser && ! $firstUser->hasRole('super_administrador')) {
            $firstUser->assignRole('super_administrador');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
