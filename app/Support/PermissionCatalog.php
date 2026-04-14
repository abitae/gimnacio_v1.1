<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

final class PermissionCatalog
{
    public const SUPER_ADMIN_ROLE_NAME = 'super_administrador';

    public const BRANCH_ADMIN_ROLE_NAME = 'administrador_sucursal';

    /** @var list<string> */
    public const LEGACY_SUPER_ADMIN_ROLE_NAMES = [
        'super-admin',
    ];

    /** @var list<string> */
    public const LEGACY_BRANCH_ADMIN_ROLE_NAMES = [
        'administrador',
    ];

    /**
     * @return array<string, array{label:string, actions:array<string, string>}>
     */
    public static function modules(): array
    {
        return [
            'cliente' => [
                'label' => 'Clientes',
                'actions' => self::crudDescriptions('clientes'),
            ],
            'ejercicio_rutina' => [
                'label' => 'Ejercicios y rutinas',
                'actions' => self::crudDescriptions('ejercicios y rutinas'),
            ],
            'membresia' => [
                'label' => 'Membresías',
                'actions' => self::crudDescriptions('membresias'),
            ],
            'matricula_cliente' => [
                'label' => 'Matrículas',
                'actions' => self::crudDescriptions('matriculas de clientes'),
            ],
            'clase' => [
                'label' => 'Clases',
                'actions' => self::crudDescriptions('clases'),
            ],
            'caja' => [
                'label' => 'Cajas',
                'actions' => self::crudDescriptions('cajas'),
            ],
            'checking' => [
                'label' => 'Checking',
                'actions' => self::crudDescriptions('registros de checking'),
            ],
            'punto_venta' => [
                'label' => 'Punto de venta',
                'actions' => self::crudDescriptions('operaciones del punto de venta'),
            ],
            'cupon' => [
                'label' => 'Cupones',
                'actions' => self::crudDescriptions('cupones'),
            ],
            'metodo_pago' => [
                'label' => 'Métodos de pago',
                'actions' => self::crudDescriptions('metodos de pago'),
            ],
            'categoria_producto' => [
                'label' => 'Categorías de productos',
                'actions' => self::crudDescriptions('categorias de productos'),
            ],
            'producto' => [
                'label' => 'Productos',
                'actions' => self::crudDescriptions('productos'),
            ],
            'servicio' => [
                'label' => 'Servicios',
                'actions' => self::crudDescriptions('servicios'),
            ],
            'gestion_nutricional' => [
                'label' => 'Gestión nutricional',
                'actions' => self::crudDescriptions('gestion nutricional'),
            ],
            'crm_mensaje' => [
                'label' => 'CRM mensajes',
                'actions' => self::crudDescriptions('mensajes del CRM'),
            ],
            'crm' => [
                'label' => 'CRM',
                'actions' => self::crudDescriptions('modulo CRM'),
            ],
            'usuario' => [
                'label' => 'Usuarios',
                'actions' => self::crudDescriptions('usuarios'),
            ],
            'rol' => [
                'label' => 'Roles',
                'actions' => self::crudDescriptions('roles'),
            ],
            'biotime' => [
                'label' => 'BioTime',
                'actions' => self::crudDescriptions('configuracion de BioTime'),
            ],
            'reporte' => [
                'label' => 'Reportes',
                'actions' => self::crudDescriptions('reportes'),
            ],
            'alquiler' => [
                'label' => 'Alquileres',
                'actions' => self::crudDescriptions('alquileres'),
            ],
            'empleado' => [
                'label' => 'Empleados',
                'actions' => self::crudDescriptions('empleados'),
            ],
            'asistencia_empleado' => [
                'label' => 'Asistencia de empleados',
                'actions' => self::crudDescriptions('asistencia de empleados'),
            ],
            'importacion' => [
                'label' => 'Importación de datos',
                'actions' => self::crudDescriptions('importación Excel legacy'),
            ],
        ];
    }

    /**
     * @return array<string, array{group:string, descripcion:string}>
     */
    public static function extraPermissions(): array
    {
        return [
            'caja.movimiento_manual' => [
                'group' => 'Cajas',
                'descripcion' => 'Registrar movimientos manuales y cruces de caja.',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function roleNames(): array
    {
        return array_keys(self::roleDefinitions());
    }

    /**
     * @return array<string, array{label:string, permissions:list<string>}>
     */
    public static function roleDefinitions(): array
    {
        return [
            self::SUPER_ADMIN_ROLE_NAME => [
                'label' => 'Super administrador',
                'permissions' => [],
            ],
            self::BRANCH_ADMIN_ROLE_NAME => [
                'label' => 'Administrador de sucursal',
                'permissions' => self::permissionNames(),
            ],
            'trainer' => [
                'label' => 'Trainer',
                'permissions' => [
                    'cliente.ver',
                    'cliente.editar',
                    'clase.ver',
                    'matricula_cliente.ver',
                    'gestion_nutricional.ver',
                    'gestion_nutricional.crear',
                    'gestion_nutricional.editar',
                    'gestion_nutricional.eliminar',
                    'checking.ver',
                    'checking.crear',
                    'checking.editar',
                    'ejercicio_rutina.ver',
                    'ejercicio_rutina.crear',
                    'ejercicio_rutina.editar',
                    'ejercicio_rutina.eliminar',
                ],
            ],
            'caja' => [
                'label' => 'Caja',
                'permissions' => [
                    'caja.ver',
                    'caja.crear',
                    'caja.editar',
                    'caja.movimiento_manual',
                    'punto_venta.ver',
                    'punto_venta.crear',
                    'reporte.ver',
                    'cliente.ver',
                    'matricula_cliente.ver',
                    'matricula_cliente.editar',
                    'metodo_pago.ver',
                    'alquiler.ver',
                    'alquiler.crear',
                    'alquiler.editar',
                    'empleado.ver',
                    'asistencia_empleado.ver',
                    'asistencia_empleado.crear',
                ],
            ],
            'vendedor' => [
                'label' => 'Vendedor',
                'permissions' => [
                    'punto_venta.ver',
                    'punto_venta.crear',
                    'cliente.ver',
                    'membresia.ver',
                    'clase.ver',
                    'matricula_cliente.ver',
                    'matricula_cliente.crear',
                    'matricula_cliente.editar',
                    'crm.ver',
                    'crm.crear',
                    'crm.editar',
                    'crm_mensaje.ver',
                    'crm_mensaje.crear',
                    'cupon.ver',
                    'producto.ver',
                    'categoria_producto.ver',
                ],
            ],
            'cafetin' => [
                'label' => 'Cafetín',
                'permissions' => [
                    'punto_venta.ver',
                    'punto_venta.crear',
                    'producto.ver',
                    'categoria_producto.ver',
                ],
            ],
            'nutricionista' => [
                'label' => 'Nutricionista',
                'permissions' => [
                    'gestion_nutricional.ver',
                    'gestion_nutricional.crear',
                    'gestion_nutricional.editar',
                    'gestion_nutricional.eliminar',
                    'cliente.ver',
                    'crm_mensaje.ver',
                    'crm_mensaje.crear',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name:string, descripcion:string, group:string}>
     */
    public static function permissions(): array
    {
        $permissions = [];

        foreach (self::modules() as $resource => $module) {
            foreach ($module['actions'] as $action => $descripcion) {
                $permissions[] = [
                    'name' => "{$resource}.{$action}",
                    'descripcion' => $descripcion,
                    'group' => $module['label'],
                ];
            }
        }

        foreach (self::extraPermissions() as $name => $meta) {
            $permissions[] = [
                'name' => $name,
                'descripcion' => $meta['descripcion'],
                'group' => $meta['group'],
            ];
        }

        return $permissions;
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return array_values(array_map(
            static fn (array $permission) => $permission['name'],
            self::permissions()
        ));
    }

    /**
     * @return list<string>
     */
    public static function sync(?string $guard = null): array
    {
        $guard ??= config('auth.defaults.guard');
        $activePermissions = [];
        $supportsDescription = Schema::hasColumn(config('permission.table_names.permissions', 'permissions'), 'descripcion');

        foreach (self::permissions() as $permission) {
            $attributes = [
                'name' => $permission['name'],
                'guard_name' => $guard,
            ];

            $values = $supportsDescription
                ? ['descripcion' => $permission['descripcion']]
                : [];

            Permission::query()->updateOrCreate($attributes, $values);
            $activePermissions[] = $permission['name'];
        }

        Permission::query()
            ->where('guard_name', $guard)
            ->whereNotIn('name', $activePermissions)
            ->where(function ($query) {
                foreach (array_merge(self::legacyPermissionNames(), ['manage-users', 'manage-roles']) as $legacyName) {
                    $query->orWhere('name', $legacyName);
                }
            })
            ->delete();

        return $activePermissions;
    }

    public static function permissionsForRole(string $roleName): array
    {
        return self::roleDefinitions()[$roleName]['permissions'] ?? [];
    }

    public static function migrateLegacyRoles(?string $guard = null): void
    {
        $guard ??= config('auth.defaults.guard');

        self::migrateLegacyRoleNames(self::LEGACY_SUPER_ADMIN_ROLE_NAMES, self::SUPER_ADMIN_ROLE_NAME, $guard);
        self::migrateLegacyRoleNames(self::LEGACY_BRANCH_ADMIN_ROLE_NAMES, self::BRANCH_ADMIN_ROLE_NAME, $guard);
    }

    /**
     * @return list<string>
     */
    public static function legacyPermissionNames(): array
    {
        return [
            'clientes.view',
            'clientes.create',
            'clientes.update',
            'clientes.delete',
            'ejercicios-rutinas.view',
            'ejercicios-rutinas.create',
            'ejercicios-rutinas.update',
            'ejercicios-rutinas.delete',
            'membresias.view',
            'membresias.create',
            'membresias.update',
            'membresias.delete',
            'cliente-matriculas.view',
            'cliente-matriculas.create',
            'cliente-matriculas.update',
            'cliente-matriculas.delete',
            'cliente-membresias.view',
            'cliente-membresias.create',
            'cliente-membresias.update',
            'cliente-membresias.delete',
            'clases.view',
            'clases.create',
            'clases.update',
            'clases.delete',
            'cajas.view',
            'cajas.create',
            'cajas.update',
            'cajas.delete',
            'cajas.movimientos-manuales',
            'checking.view',
            'checking.create',
            'checking.update',
            'checking.delete',
            'pos.view',
            'pos.create',
            'pos.update',
            'pos.delete',
            'cupones.view',
            'cupones.create',
            'cupones.update',
            'cupones.delete',
            'payment-methods.view',
            'payment-methods.create',
            'payment-methods.update',
            'payment-methods.delete',
            'categorias-productos.view',
            'categorias-productos.create',
            'categorias-productos.update',
            'categorias-productos.delete',
            'productos.view',
            'productos.create',
            'productos.update',
            'productos.delete',
            'servicios.view',
            'servicios.create',
            'servicios.update',
            'servicios.delete',
            'gestion-nutricional.view',
            'gestion-nutricional.create',
            'gestion-nutricional.update',
            'gestion-nutricional.delete',
            'crm-mensajes.view',
            'crm-mensajes.create',
            'crm-mensajes.update',
            'crm-mensajes.delete',
            'crm.view',
            'crm.create',
            'crm.update',
            'crm.delete',
            'usuarios.view',
            'usuarios.create',
            'usuarios.update',
            'usuarios.delete',
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',
            'biotime.view',
            'biotime.create',
            'biotime.update',
            'biotime.delete',
            'reportes.view',
            'reportes.create',
            'reportes.update',
            'reportes.delete',
            'rentals.view',
            'rentals.create',
            'rentals.update',
            'rentals.delete',
            'employees.view',
            'employees.create',
            'employees.update',
            'employees.delete',
            'attendance.view',
            'attendance.create',
            'attendance.update',
            'attendance.delete',
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function crudDescriptions(string $subject): array
    {
        return [
            'ver' => 'Ver '.$subject.'.',
            'crear' => 'Crear '.$subject.'.',
            'editar' => 'Editar '.$subject.'.',
            'eliminar' => 'Eliminar '.$subject.'.',
        ];
    }

    /**
     * @param  list<string>  $legacyNames
     */
    private static function migrateLegacyRoleNames(array $legacyNames, string $newName, string $guard): void
    {
        $new = Role::firstOrCreate(['name' => $newName, 'guard_name' => $guard]);

        foreach ($legacyNames as $legacyName) {
            $old = Role::query()
                ->where('name', $legacyName)
                ->where('guard_name', $guard)
                ->first();

            if (! $old) {
                continue;
            }

            foreach ($old->users()->get() as $user) {
                if (! $user->hasRole($newName)) {
                    $user->assignRole($new);
                }

                $user->removeRole($old);
            }

            $old->delete();
        }
    }
}
