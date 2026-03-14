@php
    $routeName = request()->route()?->getName();
    if (! $routeName || $routeName === 'dashboard') {
        $segments = [['label' => __('Inicio'), 'url' => route('dashboard')]];
    } else {
        $labels = [
            'clientes.index' => __('Clientes'),
            'clientes.perfil.index' => __('Perfil de cliente'),
            'clientes.perfil' => __('Perfil de cliente'),
            'membresias.index' => __('Membresías'),
            'cliente-matriculas.index' => __('Matrículas'),
            'cliente-matriculas.cuotas' => __('Cronograma de cuotas'),
            'cliente-matriculas.cuotas.crear' => __('Crear plan de cuotas'),
            'cuotas.pagar' => __('Pagar cuota'),
            'clases.index' => __('Clases'),
            'checking.index' => __('Checking'),
            'cajas.index' => __('Caja'),
            'pos.index' => __('Punto de Venta'),
            'pos.ventas-credito' => __('Ventas a crédito'),
            'pos.cuentas-por-cobrar' => __('Cobros pendientes'),
            'cupones.index' => __('Cupones'),
            'cupones.create' => __('Crear cupón'),
            'cupones.edit' => __('Editar cupón'),
            'cupones.show' => __('Detalle cupón'),
            'categorias-productos.index' => __('Categorías Productos'),
            'productos.index' => __('Productos'),
            'servicios.index' => __('Servicios'),
            'gestion-nutricional.index' => __('Gestión Nutricional'),
            'gestion-nutricional.calendario' => __('Calendario'),
            'gestion-nutricional.objetivos.index' => __('Objetivos'),
            'crm.mensajes' => __('Mensajes WhatsApp'),
            'crm.pipeline' => __('Pipeline'),
            'crm.leads.index' => __('Leads'),
            'crm.leads.show' => __('Detalle lead'),
            'crm.tareas' => __('Tareas CRM'),
            'crm.deals' => __('Oportunidades'),
            'crm.reportes' => __('Reportes CRM'),
            'crm.etiquetas' => __('Etiquetas'),
            'crm.renovacion-reactivacion' => __('Renovación y reactivación'),
            'crm.campaigns' => __('Campañas'),
            'crm.campaigns.show' => __('Detalle campaña'),
            'crm.clientes.etiquetas' => __('Etiquetas cliente'),
            'rentals.spaces.index' => __('Espacios'),
            'rentals.calendar.index' => __('Calendario de alquileres'),
            'rentals.bookings.create' => __('Crear reserva'),
            'rentals.bookings.edit' => __('Editar reserva'),
            'rentals.bookings.show' => __('Detalle reserva'),
            'rentals.report' => __('Ingresos por alquiler'),
            'employees.index' => __('Empleados'),
            'employees.create' => __('Crear empleado'),
            'employees.edit' => __('Editar empleado'),
            'employees.show' => __('Detalle empleado'),
            'employees.attendances.index' => __('Asistencia del personal'),
            'employees.attendances.create' => __('Registrar asistencia'),
            'employees.attendances.report' => __('Reporte de asistencia'),
            'usuarios.index' => __('Usuarios'),
            'roles.index' => __('Roles'),
            'payment-methods.index' => __('Métodos de pago'),
            'biotime.index' => __('BioTime'),
            'biotime.config' => __('Configuración BioTime'),
            'biotime.sync' => __('Sincronizar BioTime'),
            'biotime.areas' => __('Áreas BioTime'),
            'biotime.departments' => __('Departamentos BioTime'),
            'biotime.employees' => __('Empleados BioTime'),
            'reportes.index' => __('Centro de reportes'),
            'reportes.ventas' => __('Reporte de Ventas'),
            'reportes.matriculas' => __('Reporte de Matrículas'),
            'reportes.financiero' => __('Reporte Financiero'),
            'reportes.clientes' => __('Reporte de Clientes'),
            'reportes.clientes-membresia-clases' => __('Membresía y clases activas'),
            'reportes.usuarios' => __('Reporte de Usuarios'),
            'reportes.cajas' => __('Reporte de Cajas'),
            'reportes.productos-servicios' => __('Reporte Productos y Servicios'),
            'reportes.gimnasio' => __('Reporte del Gimnasio'),
            'reportes.cuentas-por-cobrar' => __('Cuentas por cobrar'),
            'reportes.cuotas-vencidas' => __('Cuotas vencidas'),
            'reportes.evaluacion.preview' => __('Vista previa de evaluación'),
            'reportes.evaluacion.descargar' => __('Descargar evaluación'),
            'reportes.historial-cliente.preview' => __('Vista previa de historial'),
            'reportes.historial-cliente.descargar' => __('Descargar historial'),
            'reportes.composicion-corporal.preview' => __('Vista previa de composición corporal'),
            'reportes.composicion-corporal.descargar' => __('Descargar composición corporal'),
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
            'profile.edit' => __('Perfil y ajustes'),
            'user-password.edit' => __('Contraseña'),
            'appearance.edit' => __('Apariencia'),
            'two-factor.show' => __('Autenticación en dos pasos'),
        ];
        $segments = [['label' => __('Inicio'), 'url' => route('dashboard')]];
        if (isset($labels[$routeName])) {
            $urls = [
                'gestion-nutricional.calendario' => route('gestion-nutricional.calendario'),
                'gestion-nutricional.objetivos.index' => route('gestion-nutricional.objetivos.index'),
                'biotime.config' => route('biotime.config'),
                'biotime.sync' => route('biotime.sync'),
                'biotime.areas' => route('biotime.areas'),
                'biotime.departments' => route('biotime.departments'),
                'biotime.employees' => route('biotime.employees'),
            ];
            $parentLabel = null;
            $parentRoute = null;
            if (str_starts_with($routeName, 'clientes.') || str_starts_with($routeName, 'membresias.') || str_starts_with($routeName, 'cliente-matriculas.') || $routeName === 'cuotas.pagar' || str_starts_with($routeName, 'clases.')) {
                $parentLabel = __('Clientes');
                $parentRoute = 'clientes.index';
            }
            if (str_starts_with($routeName, 'checking.') || str_starts_with($routeName, 'cajas.') || str_starts_with($routeName, 'pos.')) {
                $parentLabel = __('Operación diaria');
                $parentRoute = 'checking.index';
            }
            if (str_starts_with($routeName, 'gestion-nutricional.') && $routeName !== 'gestion-nutricional.index') {
                $parentLabel = __('Bienestar');
                $parentRoute = 'gestion-nutricional.index';
            }
            if (str_starts_with($routeName, 'crm.') && $routeName !== 'crm.mensajes') {
                $parentLabel = __('Comercial');
                $parentRoute = 'crm.pipeline';
            }
            if (str_starts_with($routeName, 'cupones.')) {
                $parentLabel = __('Comercial');
                $parentRoute = 'crm.pipeline';
            }
            if (str_starts_with($routeName, 'rentals.') || str_starts_with($routeName, 'categorias-productos.') || str_starts_with($routeName, 'productos.') || str_starts_with($routeName, 'servicios.')) {
                $parentLabel = __('Recursos');
                $parentRoute = str_starts_with($routeName, 'rentals.') ? 'rentals.spaces.index' : 'productos.index';
            }
            if (str_starts_with($routeName, 'reportes.')) {
                $parentLabel = __('Analítica');
                $parentRoute = 'reportes.index';
            }
            if (str_starts_with($routeName, 'biotime.') || str_starts_with($routeName, 'payment-methods.') || str_starts_with($routeName, 'usuarios.') || str_starts_with($routeName, 'roles.') || str_starts_with($routeName, 'employees.')) {
                $parentLabel = __('Administración');
                $parentRoute = str_starts_with($routeName, 'employees.') ? 'employees.index' : (str_starts_with($routeName, 'biotime.') ? 'biotime.index' : (str_starts_with($routeName, 'payment-methods.') ? 'payment-methods.index' : 'usuarios.index'));
            }
            if (str_starts_with($routeName, 'ejercicios.') || str_starts_with($routeName, 'rutinas-base.') || str_starts_with($routeName, 'clientes.rutinas.') || str_starts_with($routeName, 'clientes.sesiones.') || str_starts_with($routeName, 'ejercicios-rutinas.')) {
                $parentLabel = __('Bienestar');
                $parentRoute = 'ejercicios.index';
            }
            if ($parentLabel) {
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
