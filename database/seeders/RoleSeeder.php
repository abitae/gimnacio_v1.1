<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $guard = config('auth.defaults.guard');

        // Roles del sistema
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

        // Permisos legacy (se mantienen por compatibilidad)
        Permission::firstOrCreate(['name' => 'manage-users', 'guard_name' => $guard]);
        Permission::firstOrCreate(['name' => 'manage-roles', 'guard_name' => $guard]);

        // Permisos CRUD por módulo: {recurso}.view, .create, .update, .delete
        $resources = [
            'clientes',
            'ejercicios-rutinas',
            'membresias',
            'cliente-matriculas',
            'clases',
            'cliente-membresias',
            'cajas',
            'checking',
            'pos',
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

        // Super administrador y administrador tienen todos los permisos
        $superAdmin = Role::findByName('super_administrador', $guard);
        $superAdmin->givePermissionTo(array_merge(['manage-users', 'manage-roles'], $allCrudPermissions));

        $admin = Role::findByName('administrador', $guard);
        $admin->givePermissionTo(array_merge(['manage-users', 'manage-roles'], $allCrudPermissions));

        // Trainer: clientes (ver/editar), clases (ver), gestión nutricional (completo), cliente-matrículas (ver), ejercicios-rutinas (completo)
        $trainer = Role::findByName('trainer', $guard);
        $trainer->syncPermissions([
            'clientes.view', 'clientes.update',
            'clases.view',
            'cliente-matriculas.view',
            'gestion-nutricional.view', 'gestion-nutricional.create', 'gestion-nutricional.update', 'gestion-nutricional.delete',
            'checking.view', 'checking.create', 'checking.update',
            'ejercicios-rutinas.view', 'ejercicios-rutinas.create', 'ejercicios-rutinas.update', 'ejercicios-rutinas.delete',
        ]);

        // Caja: cajas y POS (ver, crear, actualizar), reportes (ver)
        $caja = Role::findByName('caja', $guard);
        $caja->syncPermissions([
            'cajas.view', 'cajas.create', 'cajas.update',
            'pos.view', 'pos.create',
            'reportes.view',
        ]);

        // Vendedor: POS, productos y categorías (ver/crear/actualizar), clientes (ver)
        $vendedor = Role::findByName('vendedor', $guard);
        $vendedor->syncPermissions([
            'pos.view', 'pos.create',
            'productos.view', 'productos.create', 'productos.update',
            'categorias-productos.view', 'categorias-productos.create', 'categorias-productos.update',
            'clientes.view', 'membresias.view',
        ]);

        // Cafetín: productos, categorías, POS (ver/crear/actualizar)
        $cafetin = Role::findByName('cafetin', $guard);
        $cafetin->syncPermissions([
            'pos.view', 'pos.create',
            'productos.view', 'productos.create', 'productos.update',
            'categorias-productos.view', 'categorias-productos.update',
        ]);

        // Nutricionista: gestión nutricional completo, clientes (ver), CRM mensajes (ver/crear)
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
    }
}
