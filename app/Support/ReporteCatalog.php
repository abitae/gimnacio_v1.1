<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;

final class ReporteCatalog
{
    /** Permiso legacy: acceso a todos los reportes analíticos. */
    public const PERMISSION_ALL = 'reporte.ver';

    /**
     * @return array<string, array{
     *     permission: string,
     *     route: string,
     *     label: string,
     *     sidebar_label: string,
     *     description: string,
     *     icon: string
     * }>
     */
    public static function definitions(): array
    {
        return [
            'gimnasio' => [
                'permission' => 'reporte.gimnasio',
                'route' => 'reportes.gimnasio',
                'label' => 'Reporte del Gimnasio',
                'sidebar_label' => 'Gimnasio',
                'description' => 'Resumen ejecutivo del negocio',
                'icon' => 'building-office-2',
            ],
            'ventas' => [
                'permission' => 'reporte.ventas',
                'route' => 'reportes.ventas',
                'label' => 'Reporte de Ventas',
                'sidebar_label' => 'Ventas',
                'description' => 'Ventas por período y método de pago',
                'icon' => 'shopping-cart',
            ],
            'matriculas' => [
                'permission' => 'reporte.matriculas',
                'route' => 'reportes.matriculas',
                'label' => 'Reporte de Matrículas',
                'sidebar_label' => 'Matrículas',
                'description' => 'Membresías y clases contratadas',
                'icon' => 'user-group',
            ],
            'financiero' => [
                'permission' => 'reporte.financiero',
                'route' => 'reportes.financiero',
                'label' => 'Reporte Financiero',
                'sidebar_label' => 'Financiero',
                'description' => 'Ingresos, pagos y resumen',
                'icon' => 'currency-dollar',
            ],
            'clientes' => [
                'permission' => 'reporte.clientes',
                'route' => 'reportes.clientes',
                'label' => 'Reporte de Clientes',
                'sidebar_label' => 'Clientes',
                'description' => 'Clientes por estado y actividad',
                'icon' => 'users',
            ],
            'clientes-membresia-clases' => [
                'permission' => 'reporte.clientes_membresia_clases',
                'route' => 'reportes.clientes-membresia-clases',
                'label' => 'Membresía y clases activas',
                'sidebar_label' => 'Memb. y clases',
                'description' => 'Clientes con membresía/clases activas y pagos',
                'icon' => 'identification',
            ],
            'usuarios' => [
                'permission' => 'reporte.usuarios',
                'route' => 'reportes.usuarios',
                'label' => 'Reporte de Usuarios',
                'sidebar_label' => 'Usuarios',
                'description' => 'Ventas y actividad por usuario',
                'icon' => 'user-circle',
            ],
            'cajas' => [
                'permission' => 'reporte.cajas',
                'route' => 'reportes.cajas',
                'label' => 'Reporte de Cajas',
                'sidebar_label' => 'Cajas',
                'description' => 'Aperturas, cierres e ingresos',
                'icon' => 'banknotes',
            ],
            'productos-servicios' => [
                'permission' => 'reporte.productos_servicios',
                'route' => 'reportes.productos-servicios',
                'label' => 'Productos y Servicios',
                'sidebar_label' => 'Productos',
                'description' => 'Más vendidos y stock bajo',
                'icon' => 'cube',
            ],
            'cuentas-por-cobrar' => [
                'permission' => 'reporte.cuentas_por_cobrar',
                'route' => 'reportes.cuentas-por-cobrar',
                'label' => 'Cuentas por cobrar',
                'sidebar_label' => 'CxC',
                'description' => 'Deudas y ventas a crédito',
                'icon' => 'document-text',
            ],
            'cuotas-vencidas' => [
                'permission' => 'reporte.cuotas_vencidas',
                'route' => 'reportes.cuotas-vencidas',
                'label' => 'Cuotas vencidas',
                'sidebar_label' => 'Cuotas vencidas',
                'description' => 'Cuotas de matrícula pendientes de pago',
                'icon' => 'currency-dollar',
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function permissionNames(): array
    {
        return array_map(
            fn (array $def): string => $def['permission'],
            self::definitions()
        );
    }

    public static function permissionFor(string $slug): string
    {
        return self::definitions()[$slug]['permission'] ?? 'reporte.'.$slug;
    }

    public static function middlewareFor(string $slug): string
    {
        return 'permission:'.self::permissionFor($slug).'|'.self::PERMISSION_ALL;
    }

    public static function middlewareAny(): string
    {
        $parts = array_merge([self::PERMISSION_ALL], self::permissionNames());

        return 'permission:'.implode('|', $parts);
    }

    public static function userCanView(?User $user, string $slug): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can(self::PERMISSION_ALL)) {
            return true;
        }

        return $user->can(self::permissionFor($slug));
    }

    public static function userCanAccessAny(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->can(self::PERMISSION_ALL)) {
            return true;
        }

        foreach (self::definitions() as $slug => $_def) {
            if ($user->can(self::permissionFor($slug))) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, array{permission: string, route: string, label: string, sidebar_label: string, description: string, icon: string}>
     */
    public static function visibleFor(?User $user): array
    {
        return array_filter(
            self::definitions(),
            fn (array $_def, string $slug): bool => self::userCanView($user, $slug),
            ARRAY_FILTER_USE_BOTH
        );
    }

    public static function authorize(string $slug): void
    {
        if (! self::userCanView(auth()->user(), $slug)) {
            abort(403);
        }
    }

    public static function authorizeAny(): void
    {
        if (! self::userCanAccessAny(auth()->user())) {
            abort(403);
        }
    }
}
