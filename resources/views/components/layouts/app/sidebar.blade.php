<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $bodyAppearanceClass ?? 'light' }} {{ $accentClass ?? 'accent-red' }}" data-appearance="{{ $appearanceValue ?? 'light' }}" data-appearance-sidebar="{{ $appearanceSidebarValue ?? 'light' }}" data-appearance-header="{{ $appearanceHeaderValue ?? 'light' }}" data-accent="{{ $accentValue ?? 'red' }}" data-sidebar-bg="{{ $sidebarBgValue ?? 'red' }}" data-header-bg="{{ $headerBgValue ?? 'red' }}" data-body-bg="{{ $bodyBgValue ?? 'default' }}" data-font-size="{{ $fontSizeValue ?? 'base' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased transition-colors {{ $fontSizeClass ?? 'text-base' }} {{ $bodyBgClass ?? 'bg-white' }}">
        @php
            use App\Support\ReporteCatalog;
        @endphp
        <flux:sidebar id="app-sidebar" sticky collapsible class="{{ $sidebarAppearanceClass ?? 'light' }} {{ $sidebarBgClass ?? 'bg-zinc-50 border-r border-zinc-200' }}">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 py-2 min-w-0 in-data-flux-sidebar-collapsed-desktop:w-10 in-data-flux-sidebar-collapsed-desktop:justify-center in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:py-0" wire:navigate>
                    @if (!empty($appBrandLogoUrl))
                        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name', 'Firnetness') }}" class="h-8 max-h-8 w-auto shrink-0 object-contain in-data-flux-sidebar-collapsed-desktop:h-6 in-data-flux-sidebar-collapsed-desktop:max-h-6" />
                    @else
                        <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-zinc-900 text-xs font-bold tracking-[0.2em] text-white dark:bg-white dark:text-zinc-900 in-data-flux-sidebar-collapsed-desktop:h-6 in-data-flux-sidebar-collapsed-desktop:w-6 in-data-flux-sidebar-collapsed-desktop:text-[10px]">F</span>
                    @endif
                    <div class="min-w-0 in-data-flux-sidebar-collapsed-desktop:hidden">
                        <div class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $appBrandName ?? config('app.name', 'Firnetness') }}</div>
                        <div class="truncate text-[11px] text-zinc-500 dark:text-zinc-400">Fitness, cobranza y operación</div>
                    </div>
                </a>
            </flux:sidebar.header>

            <flux:sidebar.search placeholder="Buscar..." />

            <flux:sidebar.nav>
                @if (!empty($availableSucursales) && $availableSucursales->count() > 1)
                    <div class="px-2 pb-2 in-data-flux-sidebar-collapsed-desktop:hidden">
                        <form method="POST" action="{{ route('sucursal-context.store') }}" class="space-y-2">
                            @csrf
                            <div class="text-xs font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Sucursal activa</div>
                            <select
                                name="sucursal_id"
                                onchange="this.form.submit()"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                            >
                                @foreach ($availableSucursales as $sucursalOption)
                                    <option value="{{ $sucursalOption->id }}" @selected(($activeSucursal?->id ?? null) === $sucursalOption->id)>
                                        {{ $sucursalOption->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                @endif

                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Inicio') }}
                </flux:sidebar.item>

                @auth
                <flux:sidebar.group expandable icon="briefcase" heading="Operaciones" class="grid" :expanded="request()->routeIs('checking.*') || request()->routeIs('cajas.*') || request()->routeIs('pos.*')">
                    @can('checking.ver')
                    <flux:sidebar.item icon="check-circle" :href="route('checking.index')" :current="request()->routeIs('checking.*')" wire:navigate>
                        {{ __('Checking') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('caja.ver')
                    <flux:sidebar.item icon="banknotes" :href="route('cajas.index')" :current="request()->routeIs('cajas.*')" wire:navigate>
                        {{ __('Caja') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('punto_venta.ver')
                    <flux:sidebar.item icon="shopping-cart" :href="route('pos.index')" :current="request()->routeIs('pos.index')" wire:navigate>
                        {{ __('Punto de venta') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="credit-card" :href="route('pos.ventas-credito')" :current="request()->routeIs('pos.ventas-credito')" wire:navigate>
                        {{ __('Ventas a credito') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-text" :href="route('pos.cuentas-por-cobrar')" :current="request()->routeIs('pos.cuentas-por-cobrar')" wire:navigate>
                        {{ __('Cobros pendientes') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can('biotime.ver')
                <flux:sidebar.group expandable icon="finger-print" heading="BioTime" class="grid" :expanded="request()->routeIs('biotime.*')">
                    <flux:sidebar.item icon="chart-bar" :href="route('biotime.index', ['tab' => 'dashboard'])" :current="request()->routeIs('biotime.*') && request()->query('tab', 'dashboard') === 'dashboard'" wire:navigate>
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('biotime.index', ['tab' => 'sedes'])" :current="request()->routeIs('biotime.*') && request()->query('tab') === 'sedes'" wire:navigate>
                        {{ __('Sedes') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map" :href="route('biotime.index', ['tab' => 'mapping'])" :current="request()->routeIs('biotime.*') && request()->query('tab') === 'mapping'" wire:navigate>
                        {{ __('Mapeo') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('biotime.index', ['tab' => 'history'])" :current="request()->routeIs('biotime.*') && request()->query('tab') === 'history'" wire:navigate>
                        {{ __('Historial') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan
                @endauth

                @canany(['cliente.ver', 'membresia.ver', 'matricula_cliente.ver', 'clase.ver'])
                <flux:sidebar.group expandable icon="users" heading="Clientes" class="grid" :expanded="request()->routeIs('clientes.*') || request()->routeIs('membresias.*') || request()->routeIs('cliente-matriculas.*') || request()->routeIs('clases.*')">
                    @can('cliente.ver')
                    <flux:sidebar.item icon="user-circle" :href="route('clientes.perfil.index')" :current="request()->routeIs('clientes.perfil*')" wire:navigate>
                        {{ __('Perfil de cliente') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('clientes.index')" :current="request()->routeIs('clientes.index')" wire:navigate>
                        {{ __('Listado de clientes') }}
                    </flux:sidebar.item>
                    @endcan
                    @canany(['membresia.ver', 'matricula_cliente.ver', 'clase.ver'])
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">
                        {{ __('Catálogo comercial') }}
                    </div>
                    @endcanany
                    @can('membresia.ver')
                    <flux:sidebar.item icon="identification" :href="route('membresias.index')" :current="request()->routeIs('membresias.*')" wire:navigate>
                        {{ __('Membresias') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('matricula_cliente.ver')
                    <flux:sidebar.item icon="user-group" :href="route('cliente-matriculas.index')" :current="request()->routeIs('cliente-matriculas.*')" wire:navigate>
                        {{ __('Matriculas') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('clase.ver')
                    <flux:sidebar.item icon="academic-cap" :href="route('clases.index')" :current="request()->routeIs('clases.*')" wire:navigate>
                        {{ __('Clases') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcanany

                @canany(['gestion_nutricional.ver', 'ejercicio_rutina.ver'])
                <flux:sidebar.group expandable icon="heart" heading="Bienestar" class="grid" :expanded="request()->routeIs('gestion-nutricional.*') || request()->routeIs('ejercicios.*') || request()->routeIs('rutinas-base.*') || request()->routeIs('clientes.rutinas.*') || request()->routeIs('clientes.sesiones.*') || request()->routeIs('ejercicios-rutinas.*')">
                    @can('gestion_nutricional.ver')
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Salud y nutrición') }}</div>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('gestion-nutricional.index')" :current="request()->routeIs('gestion-nutricional.index')" wire:navigate>
                        {{ __('Gestion nutricional') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="flag" :href="route('gestion-nutricional.objetivos.index')" :current="request()->routeIs('gestion-nutricional.objetivos.*')" wire:navigate>
                        {{ __('Objetivos') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('gestion-nutricional.calendario')" :current="request()->routeIs('gestion-nutricional.calendario')" wire:navigate>
                        {{ __('Calendario') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('ejercicio_rutina.ver')
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Entrenamiento') }}</div>
                    <flux:sidebar.item icon="fire" :href="route('ejercicios.index')" :current="request()->routeIs('ejercicios.*')" wire:navigate>
                        {{ __('Ejercicios') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('rutinas-base.index')" :current="request()->routeIs('rutinas-base.*')" wire:navigate>
                        {{ __('Rutinas base') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="user-plus" :href="route('clientes.rutinas.asignar')" :current="request()->routeIs('clientes.rutinas.asignar')" wire:navigate>
                        {{ __('Asignar rutina') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('ejercicios-rutinas.progreso')" :current="request()->routeIs('ejercicios-rutinas.progreso')" wire:navigate>
                        {{ __('Progreso') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('ejercicios-rutinas.cumplimiento')" :current="request()->routeIs('ejercicios-rutinas.cumplimiento')" wire:navigate>
                        {{ __('Cumplimiento') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcanany

                @canany(['crm.ver', 'crm_mensaje.ver', 'cupon.ver'])
                <flux:sidebar.group expandable icon="megaphone" heading="Comercial" class="grid" :expanded="request()->routeIs('crm.*') || request()->routeIs('cupones.*')">
                    @can('crm.ver')
                    <div class="px-3 pt-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('CRM') }}</div>
                    <flux:sidebar.item icon="view-columns" :href="route('crm.pipeline')" :current="request()->routeIs('crm.pipeline')" wire:navigate>
                        {{ __('Pipeline') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="list-bullet" :href="route('crm.leads.index')" :current="request()->routeIs('crm.leads.index')" wire:navigate>
                        {{ __('Leads') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('crm.tareas')" :current="request()->routeIs('crm.tareas')" wire:navigate>
                        {{ __('Tareas CRM') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="banknotes" :href="route('crm.deals')" :current="request()->routeIs('crm.deals')" wire:navigate>
                        {{ __('Oportunidades') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('crm.campaigns')" :current="request()->routeIs('crm.campaigns*')" wire:navigate>
                        {{ __('Campanas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('crm.etiquetas')" :current="request()->routeIs('crm.etiquetas')" wire:navigate>
                        {{ __('Etiquetas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="chart-bar" :href="route('crm.reportes')" :current="request()->routeIs('crm.reportes')" wire:navigate>
                        {{ __('Reportes CRM') }}
                    </flux:sidebar.item>
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Retención') }}</div>
                    <flux:sidebar.item icon="arrow-path" :href="route('crm.renovacion-reactivacion')" :current="request()->routeIs('crm.renovacion-reactivacion')" wire:navigate>
                        {{ __('Renovacion y reactivacion') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('crm_mensaje.ver')
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Promociones') }}</div>
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('crm.mensajes')" :current="request()->routeIs('crm.mensajes')" wire:navigate>
                        {{ __('Mensajes WhatsApp') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('cupon.ver')
                    <flux:sidebar.item icon="ticket" :href="route('cupones.index')" :current="request()->routeIs('cupones.*')" wire:navigate>
                        {{ __('Cupones') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcanany

                @canany(['categoria_producto.ver', 'producto.ver', 'servicio.ver', 'alquiler.ver'])
                <flux:sidebar.group expandable icon="archive-box" heading="Recursos" class="grid" :expanded="request()->routeIs('categorias-productos.*') || request()->routeIs('productos.*') || request()->routeIs('servicios.*') || request()->routeIs('rentals.*')">
                    @can('categoria_producto.ver')
                    <div class="px-3 pt-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Catálogo') }}</div>
                    <flux:sidebar.item icon="tag" :href="route('categorias-productos.index')" :current="request()->routeIs('categorias-productos.*')" wire:navigate>
                        {{ __('Categorias de productos') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('producto.ver')
                    <flux:sidebar.item icon="cube" :href="route('productos.index')" :current="request()->routeIs('productos.*')" wire:navigate>
                        {{ __('Productos') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('servicio.ver')
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('servicios.index')" :current="request()->routeIs('servicios.*')" wire:navigate>
                        {{ __('Servicios') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('alquiler.ver')
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Alquileres') }}</div>
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('rentals.operations.index')" :current="request()->routeIs('rentals.operations.*')" wire:navigate>
                        {{ __('Bandeja del día') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('rentals.spaces.index')" :current="request()->routeIs('rentals.spaces.*')" wire:navigate>
                        {{ __('Espacios') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('rentals.calendar.index')" :current="request()->routeIs('rentals.calendar.*')" wire:navigate>
                        {{ __('Calendario de alquileres') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="document-chart-bar" :href="route('rentals.report')" :current="request()->routeIs('rentals.report')" wire:navigate>
                        {{ __('Ingresos por alquiler') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcanany

                @if(ReporteCatalog::userCanAccessAny(auth()->user()))
                <flux:sidebar.group expandable icon="chart-bar" heading="Analitica" class="grid" :expanded="request()->routeIs('reportes.*')">
                    @php
                        $reportesSidebar = ReporteCatalog::visibleFor(auth()->user());
                    @endphp
                    @if (count($reportesSidebar) > 1)
                        <flux:sidebar.item icon="document-chart-bar" :href="route('reportes.index')" :current="request()->routeIs('reportes.index')" wire:navigate>
                            {{ __('Centro de reportes') }}
                        </flux:sidebar.item>
                    @endif
                    @foreach ($reportesSidebar as $reporte)
                        <flux:sidebar.item :icon="$reporte['icon']" :href="route($reporte['route'])" :current="request()->routeIs($reporte['route'])" wire:navigate>
                            {{ __($reporte['sidebar_label']) }}
                        </flux:sidebar.item>
                    @endforeach
                </flux:sidebar.group>
                @endif

                @canany(['empleado.ver', 'metodo_pago.ver', 'publicidad_app.ver', 'usuario.ver', 'rol.ver'])
                <flux:sidebar.group expandable icon="cog-6-tooth" heading="Administracion" class="grid" :expanded="request()->routeIs('employees.*') || request()->routeIs('payment-methods.*') || request()->routeIs('app-publicidad.*') || request()->routeIs('usuarios.*') || request()->routeIs('roles.*')">
                    @can('empleado.ver')
                    <div class="px-3 pt-1 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Personal') }}</div>
                    <flux:sidebar.item icon="users" :href="route('employees.index')" :current="request()->routeIs('employees.index') || request()->routeIs('employees.show') || request()->routeIs('employees.edit') || request()->routeIs('employees.create')" wire:navigate>
                        {{ __('Empleados') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clipboard-document-check" :href="route('employees.attendances.index')" :current="request()->routeIs('employees.attendances.*')" wire:navigate>
                        {{ __('Asistencia del personal') }}
                    </flux:sidebar.item>
                    @endcan
                    @canany(['metodo_pago.ver', 'publicidad_app.ver'])
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Configuración') }}</div>
                    @endcanany
                    @can('metodo_pago.ver')
                    <flux:sidebar.item icon="credit-card" :href="route('payment-methods.index')" :current="request()->routeIs('payment-methods.*')" wire:navigate>
                        {{ __('Metodos de pago') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('publicidad_app.ver')
                    <flux:sidebar.item icon="photo" :href="route('app-publicidad.index')" :current="request()->routeIs('app-publicidad.*')" wire:navigate>
                        {{ __('Publicidad app') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('usuario.ver')
                    <div class="px-3 pt-2 text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400 in-data-flux-sidebar-collapsed-desktop:hidden">{{ __('Seguridad') }}</div>
                    <flux:sidebar.item icon="user-group" :href="route('usuarios.index')" :current="request()->routeIs('usuarios.*')" wire:navigate>
                        {{ __('Usuarios') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('rol.ver')
                    <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                        {{ __('Roles') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endcanany

                @if (auth()->user()?->hasRole(\App\Support\PermissionCatalog::SUPER_ADMIN_ROLE_NAME))
                <flux:sidebar.group expandable icon="shield-check" heading="Super administracion" class="grid" :expanded="request()->routeIs('company-branches.*') || request()->routeIs('administracion.backups.*') || request()->routeIs('importaciones.*')">
                    <flux:sidebar.item icon="building-storefront" :href="route('company-branches.index')" :current="request()->routeIs('company-branches.*')" wire:navigate>
                        {{ __('Empresa y sucursales') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-up-tray" :href="route('importaciones.index')" :current="request()->routeIs('importaciones.index') || request()->routeIs('importaciones.historial') || request()->routeIs('importaciones.show')" wire:navigate>
                        {{ __('Carga inicial Excel') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="clock" :href="route('importaciones.historial')" :current="request()->routeIs('importaciones.historial')" wire:navigate>
                        {{ __('Historial importaciones') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="circle-stack" :href="route('administracion.backups.index')" :current="request()->routeIs('administracion.backups.*')" wire:navigate>
                        {{ __('Respaldos BD') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <div class="px-2 py-2 in-data-flux-sidebar-collapsed-desktop:px-0 in-data-flux-sidebar-collapsed-desktop:py-0">
                    <livewire:personalization-modal />
                </div>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*')" wire:navigate>
                    {{ __('Perfil y ajustes') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>

            <flux:dropdown position="top" align="start" class="max-lg:hidden">
                <flux:sidebar.profile 
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>{{ __('Perfil y ajustes') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Cerrar sesion') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header id="app-header" class="block! {{ $headerAppearanceClass ?? 'light' }} {{ $headerBgClass ?? 'bg-white lg:bg-zinc-100 border-b-2 border-zinc-300' }}">
            <flux:navbar class="lg:hidden w-full">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

                @if (!empty($availableSucursales) && $availableSucursales->count() > 1)
                    <form method="POST" action="{{ route('sucursal-context.store') }}" class="mx-2 min-w-0 flex-1">
                        @csrf
                        <select
                            name="sucursal_id"
                            onchange="this.form.submit()"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        >
                            @foreach ($availableSucursales as $sucursalOption)
                                <option value="{{ $sucursalOption->id }}" @selected(($activeSucursal?->id ?? null) === $sucursalOption->id)>
                                    {{ $sucursalOption->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <flux:spacer />

                <flux:dropdown position="top" align="start">
                    <flux:profile 
                        :initials="auth()->user()->initials()"
                    />

                    <flux:menu>
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="grid flex-1 text-start text-sm leading-tight">
                                        <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>{{ __('Perfil y ajustes') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                                {{ __('Cerrar sesion') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:navbar>

            <flux:navbar scrollable class="items-center">
                <flux:sidebar.collapse class="max-lg:hidden shrink-0 me-1" :tooltip="__('Alternar barra lateral')" />

                <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Inicio') }}
                </flux:navbar.item>
                @if ($activeSucursal)
                    <flux:navbar.item href="#" class="pointer-events-none opacity-80">
                        {{ __('Sucursal:') }} {{ $activeSucursal->nombre }}
                    </flux:navbar.item>
                @endif
                @auth
                @can('checking.ver')
                <flux:navbar.item :href="route('checking.index')" :current="request()->routeIs('checking.*')" wire:navigate>
                    {{ __('Checking') }}
                </flux:navbar.item>
                @endcan
                @endauth
                @can('punto_venta.ver')
                <flux:navbar.item :href="route('pos.index')" :current="request()->routeIs('pos.*')" wire:navigate>
                    {{ __('Punto de venta') }}
                </flux:navbar.item>
                @endcan
                @if(ReporteCatalog::userCanAccessAny(auth()->user()))
                <flux:navbar.item :href="route('reportes.index')" :current="request()->routeIs('reportes.*')" wire:navigate>
                    {{ __('Reportes') }}
                </flux:navbar.item>
                @endif
            </flux:navbar>
        </flux:header>

        
        {{ $slot }}
        

        @fluxScripts
        @stack('scripts')
        <script>
            (function() {
                var STORAGE_KEY = 'app-appearance';
                var sidebarBgClasses = {
                    default: 'bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700',
                    slate: 'bg-slate-50 dark:bg-slate-950 border-r border-slate-200 dark:border-slate-800',
                    blue: 'bg-blue-50 dark:bg-blue-950 border-r border-blue-200 dark:border-blue-800',
                    green: 'bg-green-50 dark:bg-green-950 border-r border-green-200 dark:border-green-800',
                    amber: 'bg-amber-50 dark:bg-amber-950 border-r border-amber-200 dark:border-amber-800',
                    red: 'bg-red-50 dark:bg-red-950 border-r border-red-200 dark:border-red-800',
                    violet: 'bg-violet-50 dark:bg-violet-950 border-r border-violet-200 dark:border-violet-800',
                    indigo: 'bg-indigo-50 dark:bg-indigo-950 border-r border-indigo-200 dark:border-indigo-800'
                };
                var headerBgClasses = {
                    default: 'bg-white lg:bg-zinc-100 dark:bg-zinc-900 border-b-2 border-zinc-300 dark:border-zinc-600',
                    slate: 'bg-white lg:bg-slate-100 dark:bg-slate-950 border-b-2 border-slate-300 dark:border-slate-700',
                    blue: 'bg-white lg:bg-blue-100 dark:bg-blue-950 border-b-2 border-blue-300 dark:border-blue-700',
                    green: 'bg-white lg:bg-green-100 dark:bg-green-950 border-b-2 border-green-300 dark:border-green-700',
                    amber: 'bg-white lg:bg-amber-100 dark:bg-amber-950 border-b-2 border-amber-300 dark:border-amber-700',
                    red: 'bg-white lg:bg-red-100 dark:bg-red-950 border-b-2 border-red-300 dark:border-red-700',
                    violet: 'bg-white lg:bg-violet-100 dark:bg-violet-950 border-b-2 border-violet-300 dark:border-violet-700',
                    indigo: 'bg-white lg:bg-indigo-100 dark:bg-indigo-950 border-b-2 border-indigo-300 dark:border-indigo-700'
                };
                var pageHeaderGradientClasses = {
                    default: 'bg-gradient-to-r from-zinc-600 to-zinc-700 dark:from-zinc-700 dark:to-zinc-800',
                    slate: 'bg-gradient-to-r from-slate-600 to-slate-700 dark:from-slate-700 dark:to-slate-800',
                    blue: 'bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800',
                    green: 'bg-gradient-to-r from-green-600 to-green-700 dark:from-green-700 dark:to-green-800',
                    amber: 'bg-gradient-to-r from-amber-600 to-amber-700 dark:from-amber-700 dark:to-amber-800',
                    red: 'bg-gradient-to-r from-red-600 to-red-700 dark:from-red-700 dark:to-red-800',
                    violet: 'bg-gradient-to-r from-violet-600 to-violet-700 dark:from-violet-700 dark:to-violet-800',
                    indigo: 'bg-gradient-to-r from-indigo-600 to-indigo-700 dark:from-indigo-700 dark:to-indigo-800'
                };
                var bodyBgClasses = {
                    default: 'bg-white dark:bg-zinc-800',
                    slate: 'bg-slate-50 dark:bg-slate-900',
                    blue: 'bg-blue-50/50 dark:bg-blue-950/50',
                    green: 'bg-green-50/50 dark:bg-green-950/50',
                    amber: 'bg-amber-50/50 dark:bg-amber-950/50',
                    red: 'bg-red-50/50 dark:bg-red-950/50',
                    violet: 'bg-violet-50/50 dark:bg-violet-950/50',
                    indigo: 'bg-indigo-50/50 dark:bg-indigo-950/50'
                };
                function resolveMode(val) {
                    return 'light';
                }
                function getStoredAppearance() {
                    try {
                        var raw = localStorage.getItem(STORAGE_KEY);
                        if (!raw) return null;
                        var data = JSON.parse(raw);
                        if (data && typeof data.appearance !== 'undefined') return data;
                    } catch (e) {}
                    return null;
                }
                /** Livewire puede pasar el objeto o un CustomEvent con detail; a veces claves en camelCase. */
                function normalizeAppearancePayload(x) {
                    if (x == null || typeof x !== 'object') return {};
                    var o = x;
                    if (typeof CustomEvent !== 'undefined' && x instanceof CustomEvent && x.detail != null) {
                        var d = x.detail;
                        if (Array.isArray(d) && d.length && typeof d[0] === 'object') o = d[0];
                        else if (typeof d === 'object') o = d;
                    }
                    if (o.fontSize != null && o.font_size == null) o.font_size = o.fontSize;
                    if (o.appearanceSidebar != null && o.appearance_sidebar == null) o.appearance_sidebar = o.appearanceSidebar;
                    if (o.appearanceHeader != null && o.appearance_header == null) o.appearance_header = o.appearanceHeader;
                    if (o.sidebarBg != null && o.sidebar_bg == null) o.sidebar_bg = o.sidebarBg;
                    if (o.headerBg != null && o.header_bg == null) o.header_bg = o.headerBg;
                    if (o.bodyBg != null && o.body_bg == null) o.body_bg = o.bodyBg;
                    return o;
                }
                var fontSizeClasses = { sm: 'text-sm', base: 'text-base', lg: 'text-lg' };
                var appearanceKeys = ['appearance', 'appearance_sidebar', 'appearance_header', 'accent', 'sidebar_bg', 'header_bg', 'body_bg', 'font_size'];
                function mergeAppearanceParams(raw) {
                    raw = normalizeAppearancePayload(raw) || {};
                    var doc = getParamsFromDocument();
                    var out = {};
                    for (var i = 0; i < appearanceKeys.length; i++) {
                        var k = appearanceKeys[i];
                        out[k] = Object.prototype.hasOwnProperty.call(raw, k) && raw[k] != null && raw[k] !== ''
                            ? raw[k]
                            : doc[k];
                    }
                    return out;
                }
                function applyAppearance(rawParams) {
                    var params = mergeAppearanceParams(rawParams);
                    var appearance = 'light';
                    var appearanceSidebar = 'light';
                    var appearanceHeader = 'light';
                    var accent = params.accent || 'red';
                    var sidebarBg = params.sidebar_bg || 'red';
                    var headerBg = params.header_bg || 'red';
                    var bodyBg = params.body_bg || 'default';
                    var fontSize = params.font_size || 'base';
                    var html = document.documentElement;
                    html.classList.remove('light', 'dark');
                    html.classList.add(resolveMode(appearance));
                    html.setAttribute('data-appearance', appearance);
                    html.setAttribute('data-appearance-sidebar', appearanceSidebar);
                    html.setAttribute('data-appearance-header', appearanceHeader);
                    html.classList.remove('accent-neutral', 'accent-blue', 'accent-green', 'accent-red', 'accent-violet', 'accent-indigo', 'accent-amber');
                    html.classList.add('accent-' + accent);
                    html.setAttribute('data-accent', accent);
                    html.setAttribute('data-sidebar-bg', sidebarBg);
                    html.setAttribute('data-header-bg', headerBg);
                    html.setAttribute('data-body-bg', bodyBg);
                    html.setAttribute('data-font-size', fontSize);
                    var sidebarMode = resolveMode(appearanceSidebar);
                    var sidebarEl = document.getElementById('app-sidebar');
                    if (sidebarEl) {
                        var base = sidebarEl.className.replace(/\b(light|dark)\b|\bbg-\w+(-\d+)?(\/\d+)?|dark:bg-\w+(-\d+)?(\/\d+)?|border-r|border-\w+(-\d+)?|dark:border-\w+(-\d+)?/g, '').replace(/\s+/g, ' ').trim();
                        sidebarEl.className = (base + ' ' + sidebarMode + ' ' + (sidebarBgClasses[sidebarBg] || sidebarBgClasses.default)).trim();
                    }
                    var headerMode = resolveMode(appearanceHeader);
                    var headerEl = document.getElementById('app-header');
                    if (headerEl) {
                        var baseH = headerEl.className.replace(/\b(light|dark)\b|\bbg-\w+(-\d+)?(\/\d+)?|dark:bg-\w+(-\d+)?(\/\d+)?|lg:bg-\w+(-\d+)?(\/\d+)?|border-b|border-\w+(-\d+)?|dark:border-\w+(-\d+)?/g, '').replace(/\s+/g, ' ').trim();
                        headerEl.className = (baseH + ' ' + headerMode + ' ' + (headerBgClasses[headerBg] || headerBgClasses.default)).trim();
                    }
                    var bodyEl = document.body;
                    if (bodyEl) {
                        var bodyParts = bodyEl.className.split(/\s+/).filter(function(c) {
                            return c && !/^bg-\w+(-\d+)?(\/\d+)?$/.test(c) && !/^dark:bg-\w+(-\d+)?(\/\d+)?$/.test(c) && c !== 'text-sm' && c !== 'text-base' && c !== 'text-lg';
                        });
                        bodyEl.className = (bodyParts.join(' ') + ' ' + (fontSizeClasses[fontSize] || fontSizeClasses.base) + ' ' + (bodyBgClasses[bodyBg] || bodyBgClasses.default)).trim();
                    }
                    var grad = pageHeaderGradientClasses[headerBg] || pageHeaderGradientClasses.default;
                    document.querySelectorAll('[data-app-page-header]').forEach(function(el) {
                        el.className = 'rounded-xl p-6 shadow-lg ' + grad;
                    });
                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify({
                            appearance: appearance,
                            appearance_sidebar: appearanceSidebar,
                            appearance_header: appearanceHeader,
                            accent: accent,
                            sidebar_bg: sidebarBg,
                            header_bg: headerBg,
                            body_bg: bodyBg,
                            font_size: fontSize
                        }));
                    } catch (e) {}
                }
                function getParamsFromDocument() {
                    return {
                        appearance: 'light',
                        appearance_sidebar: 'light',
                        appearance_header: 'light',
                        accent: document.documentElement.getAttribute('data-accent') || 'red',
                        sidebar_bg: document.documentElement.getAttribute('data-sidebar-bg') || 'red',
                        header_bg: document.documentElement.getAttribute('data-header-bg') || 'red',
                        body_bg: document.documentElement.getAttribute('data-body-bg') || 'default',
                        font_size: document.documentElement.getAttribute('data-font-size') || 'base'
                    };
                }
                /** localStorage primero, luego data-* del servidor (gana el HTML para font_size y prefs en BD). */
                function mergedStoredAndDocument() {
                    return Object.assign({}, getStoredAppearance() || {}, getParamsFromDocument());
                }
                function restoreAppearanceFromStorage() {
                    try {
                        var stored = getStoredAppearance();
                        if (stored) {
                            stored.appearance = 'light';
                            stored.appearance_sidebar = 'light';
                            stored.appearance_header = 'light';
                            localStorage.setItem(STORAGE_KEY, JSON.stringify(stored));
                        }
                    } catch (e) {}
                    applyAppearance(mergedStoredAndDocument());
                }
                document.addEventListener('livewire:init', function() {
                    applyAppearance(mergedStoredAndDocument());
                    Livewire.on('appearance-updated', applyAppearance);
                    document.addEventListener('livewire:navigated', restoreAppearanceFromStorage);
                });
                restoreAppearanceFromStorage();
                document.addEventListener('livewire:navigated', restoreAppearanceFromStorage);
            })();
        </script>
        @php
            $flashType = null;
            $flashMessage = null;
            if (session()->has('success')) { $flashType = 'success'; $flashMessage = session('success'); }
            elseif (session()->has('error')) { $flashType = 'error'; $flashMessage = session('error'); }
            elseif (session()->has('warning')) { $flashType = 'warning'; $flashMessage = session('warning'); }
            elseif (session()->has('info')) { $flashType = 'info'; $flashMessage = session('info'); }
        @endphp
        @if ($flashType)
        <script>
            window.__flashToast = { type: @json($flashType), message: @json($flashMessage) };
            window.dispatchEvent(new CustomEvent('flash-toast-pending'));
        </script>
        @endif
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    </body>
</html>
