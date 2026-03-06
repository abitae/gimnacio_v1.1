@php
    $routeName = request()->route()?->getName();
    if (! $routeName || $routeName === 'dashboard') {
        $segments = [['label' => __('Dashboard'), 'url' => route('dashboard')]];
    } else {
        $labels = [
            'clientes.index' => __('Clientes'),
            'membresias.index' => __('Membresías'),
            'cliente-matriculas.index' => __('Cliente Matrículas'),
            'clases.index' => __('Clases'),
            'checking.index' => __('Checking'),
            'cajas.index' => __('Caja'),
            'pos.index' => __('Punto de Venta'),
            'categorias-productos.index' => __('Categorías Productos'),
            'productos.index' => __('Productos'),
            'servicios.index' => __('Servicios'),
            'gestion-nutricional.index' => __('Gestión Nutricional'),
            'gestion-nutricional.calendario' => __('Calendario'),
            'crm.mensajes' => __('Mensajes WhatsApp'),
            'crm.pipeline' => __('Pipeline'),
            'crm.leads.index' => __('Leads'),
            'crm.leads.show' => __('Detalle lead'),
            'crm.tareas' => __('Tareas CRM'),
            'crm.deals' => __('Ofertas'),
            'crm.reportes' => __('Reportes CRM'),
            'crm.etiquetas' => __('Etiquetas CRM'),
            'crm.renovacion-reactivacion' => __('Renovación y reactivación'),
            'crm.campaigns' => __('Campañas'),
            'crm.campaigns.show' => __('Detalle campaña'),
            'crm.clientes.etiquetas' => __('Etiquetas cliente'),
            'usuarios.index' => __('Usuarios'),
            'roles.index' => __('Roles'),
            'biotime.index' => __('BioTime Dashboard'),
            'biotime.config' => __('Configuración BioTime'),
            'biotime.sync' => __('Sincronizar BioTime'),
            'biotime.areas' => __('Áreas BioTime'),
            'biotime.departments' => __('Departamentos BioTime'),
            'biotime.employees' => __('Empleados BioTime'),
            'reportes.index' => __('Reportes'),
            'reportes.ventas' => __('Reporte de Ventas'),
            'reportes.matriculas' => __('Reporte de Matrículas'),
            'reportes.financiero' => __('Reporte Financiero'),
            'reportes.clientes' => __('Reporte de Clientes'),
            'reportes.clientes-membresia-clases' => __('Membresía y clases activas'),
            'reportes.usuarios' => __('Reporte de Usuarios'),
            'reportes.cajas' => __('Reporte de Cajas'),
            'reportes.productos-servicios' => __('Reporte Productos y Servicios'),
            'reportes.gimnasio' => __('Reporte del Gimnasio'),
            'ejercicios.index' => __('Ejercicios'),
            'ejercicios.show' => __('Detalle ejercicio'),
            'rutinas-base.index' => __('Rutinas base'),
            'rutinas-base.show' => __('Detalle rutina base'),
            'rutinas-base.builder' => __('Builder rutina'),
            'clientes.rutinas.asignar' => __('Asignar rutina'),
            'clientes.rutinas.index' => __('Rutinas del cliente'),
            'clientes.rutinas.show' => __('Detalle rutina'),
            'clientes.rutinas.sesiones.index' => __('Sesiones'),
            'clientes.rutinas.sesiones.create' => __('Registrar sesión'),
            'clientes.sesiones.show' => __('Detalle sesión'),
            'ejercicios-rutinas.progreso' => __('Progreso por ejercicio'),
            'ejercicios-rutinas.cumplimiento' => __('Cumplimiento'),
            'profile.edit' => __('Settings'),
            'user-password.edit' => __('Contraseña'),
            'appearance.edit' => __('Apariencia'),
            'two-factor.show' => __('Autenticación en dos pasos'),
        ];
        $segments = [['label' => __('Dashboard'), 'url' => route('dashboard')]];
        if (isset($labels[$routeName])) {
            $urls = [
                'gestion-nutricional.calendario' => route('gestion-nutricional.calendario'),
                'biotime.config' => route('biotime.config'),
                'biotime.sync' => route('biotime.sync'),
                'biotime.areas' => route('biotime.areas'),
                'biotime.departments' => route('biotime.departments'),
                'biotime.employees' => route('biotime.employees'),
            ];
            $parentLabel = null;
            if (str_starts_with($routeName, 'gestion-nutricional.') && $routeName !== 'gestion-nutricional.index') {
                $parentLabel = __('Gestión Nutricional');
            }
            if (str_starts_with($routeName, 'biotime.') && $routeName !== 'biotime.index') {
                $parentLabel = __('Integración BioTime');
            }
            if (str_starts_with($routeName, 'reportes.') && $routeName !== 'reportes.index') {
                $parentLabel = __('Reportes');
            }
            if (str_starts_with($routeName, 'crm.') && $routeName !== 'crm.mensajes') {
                $parentLabel = __('CRM');
                $parentRoute = 'crm.pipeline';
            }
            if (str_starts_with($routeName, 'ejercicios.') || str_starts_with($routeName, 'rutinas-base.') || str_starts_with($routeName, 'clientes.rutinas.') || str_starts_with($routeName, 'clientes.sesiones.') || str_starts_with($routeName, 'ejercicios-rutinas.')) {
                $parentLabel = __('Ejercicios y Rutinas');
                $parentRoute = 'ejercicios.index';
            }
            if ($parentLabel) {
                $parentRoute = $parentRoute ?? match (true) {
                    str_starts_with($routeName, 'gestion-nutricional.') => 'gestion-nutricional.index',
                    str_starts_with($routeName, 'biotime.') => 'biotime.index',
                    str_starts_with($routeName, 'crm.') => 'crm.pipeline',
                    str_starts_with($routeName, 'ejercicios-rutinas.') => 'ejercicios-rutinas.progreso',
                    default => 'reportes.index',
                };
                $segments[] = ['label' => $parentLabel, 'url' => route($parentRoute)];
            }
            $segments[] = ['label' => $labels[$routeName], 'url' => $urls[$routeName] ?? null];
        }
    }
@endphp
@if (count($segments) > 0)
<nav aria-label="{{ __('Miga de pan') }}" class="mb-3 flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
    @foreach ($segments as $i => $seg)
        @if ($i > 0)
            <span aria-hidden="true">/</span>
        @endif
        @if ($i === count($segments) - 1)
            <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $seg['label'] }}</span>
        @else
            <a href="{{ $seg['url'] }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-zinc-200">{{ $seg['label'] }}</a>
        @endif
    @endforeach
</nav>
@endif
