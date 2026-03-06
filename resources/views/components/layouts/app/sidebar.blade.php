<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $bodyAppearanceClass ?? 'dark' }} {{ $accentClass ?? 'accent-neutral' }}" data-appearance="{{ $appearanceValue ?? 'system' }}" data-appearance-sidebar="{{ $appearanceSidebarValue ?? 'system' }}" data-appearance-header="{{ $appearanceHeaderValue ?? 'system' }}" data-accent="{{ $accentValue ?? 'neutral' }}" data-sidebar-bg="{{ $sidebarBgValue ?? 'default' }}" data-header-bg="{{ $headerBgValue ?? 'default' }}" data-body-bg="{{ $bodyBgValue ?? 'default' }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen antialiased transition-colors {{ $bodyBgClass ?? 'bg-white dark:bg-zinc-800' }}">
        <flux:sidebar id="app-sidebar" sticky collapsible="mobile" class="{{ $sidebarAppearanceClass ?? 'dark' }} {{ $sidebarBgClass ?? 'bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700' }}">
            <flux:sidebar.header>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2 px-2 py-2 min-w-0" wire:navigate>
                    <img src="{{ asset('Open9/logo_completo_sin_fondo.png') }}" alt="{{ config('app.name', 'Open9') }}" class="h-8 max-h-8 w-auto object-contain" />
                </a>

                <flux:sidebar.collapse class="lg:hidden" />
            </flux:sidebar.header>

            <flux:sidebar.search placeholder="Buscar..." />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>

                <flux:sidebar.group expandable heading="Gestión de Clientes" class="grid" :expanded="request()->routeIs('clientes.*') || request()->routeIs('membresias.*') || request()->routeIs('cliente-matriculas.*') || request()->routeIs('checking.*') || request()->routeIs('clases.*')">
                    @can('clientes.view')
                    <flux:sidebar.item icon="users" :href="route('clientes.index')" :current="request()->routeIs('clientes.*')" wire:navigate>
                        {{ __('Clientes') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('membresias.view')
                    <flux:sidebar.item icon="identification" :href="route('membresias.index')" :current="request()->routeIs('membresias.*')" wire:navigate>
                        {{ __('Membresías') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('clases.view')
                    <flux:sidebar.item icon="academic-cap" :href="route('clases.index')" :current="request()->routeIs('clases.*')" wire:navigate>
                        {{ __('Clases') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('cliente-matriculas.view')
                    <flux:sidebar.item icon="user-group" :href="route('cliente-matriculas.index')" :current="request()->routeIs('cliente-matriculas.*')" wire:navigate>
                        {{ __('Cliente Matrículas') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('checking.view')
                    <flux:sidebar.item icon="check-circle" :href="route('checking.index')" :current="request()->routeIs('checking.*')" wire:navigate>
                        {{ __('Checking') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can('gestion-nutricional.view')
                <flux:sidebar.group expandable heading="Gestión Nutricional" class="grid" :expanded="request()->routeIs('gestion-nutricional.*')">
                    <flux:sidebar.item icon="clipboard-document-list" :href="route('gestion-nutricional.index')" :current="request()->routeIs('gestion-nutricional.index')" wire:navigate>
                        {{ __('Gestión Nutricional') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="calendar" :href="route('gestion-nutricional.calendario')" :current="request()->routeIs('gestion-nutricional.calendario')" wire:navigate>
                        {{ __('Calendario') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan

                @can('ejercicios-rutinas.view')
                <flux:sidebar.group expandable heading="Ejercicios y Rutinas" class="grid" :expanded="request()->routeIs('ejercicios.*') || request()->routeIs('rutinas-base.*') || request()->routeIs('clientes.rutinas.*') || request()->routeIs('ejercicios-rutinas.*')">
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
                </flux:sidebar.group>
                @endcan

                <flux:sidebar.group expandable heading="CRM" class="grid" :expanded="request()->routeIs('crm.*')">
                    @can('crm.view')
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
                    <flux:sidebar.item icon="document-chart-bar" :href="route('crm.reportes')" :current="request()->routeIs('crm.reportes')" wire:navigate>
                        {{ __('Reportes CRM') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="tag" :href="route('crm.etiquetas')" :current="request()->routeIs('crm.etiquetas')" wire:navigate>
                        {{ __('Etiquetas') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-path" :href="route('crm.renovacion-reactivacion')" :current="request()->routeIs('crm.renovacion-reactivacion')" wire:navigate>
                        {{ __('Renovación / Reactivación') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="megaphone" :href="route('crm.campaigns')" :current="request()->routeIs('crm.campaigns*')" wire:navigate>
                        {{ __('Campañas') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('crm-mensajes.view')
                    <flux:sidebar.item icon="chat-bubble-left-right" :href="route('crm.mensajes')" :current="request()->routeIs('crm.mensajes')" wire:navigate>
                        {{ __('Mensajes WhatsApp') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can('reportes.view')
                <flux:sidebar.group expandable heading="Reportes" class="grid" :expanded="request()->routeIs('reportes.*')">
                    <flux:sidebar.item icon="document-chart-bar" :href="route('reportes.index')" :current="request()->routeIs('reportes.index')" wire:navigate>
                        {{ __('Reportes') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan

                <flux:sidebar.group expandable heading="Ventas" class="grid" :expanded="request()->routeIs('cajas.*') || request()->routeIs('pos.*')">
                    @can('cajas.view')
                    <flux:sidebar.item icon="banknotes" :href="route('cajas.index')" :current="request()->routeIs('cajas.*')" wire:navigate>
                        {{ __('Caja') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('pos.view')
                    <flux:sidebar.item icon="shopping-cart" :href="route('pos.index')" :current="request()->routeIs('pos.*')" wire:navigate>
                        {{ __('Punto de Venta') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                <flux:sidebar.group expandable heading="Productos" class="grid" :expanded="request()->routeIs('categorias-productos.*') || request()->routeIs('productos.*')">
                    @can('categorias-productos.view')
                    <flux:sidebar.item icon="tag" :href="route('categorias-productos.index')" :current="request()->routeIs('categorias-productos.*')" wire:navigate>
                        {{ __('Categorías Productos') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('productos.view')
                    <flux:sidebar.item icon="cube" :href="route('productos.index')" :current="request()->routeIs('productos.*')" wire:navigate>
                        {{ __('Productos') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>

                @can('servicios.view')
                <flux:sidebar.group expandable heading="Servicios" class="grid" :expanded="request()->routeIs('servicios.*')">
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('servicios.index')" :current="request()->routeIs('servicios.*')" wire:navigate>
                        {{ __('Servicios') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan

                @can('biotime.view')
                <flux:sidebar.group expandable heading="Integración BioTime" class="grid" :expanded="request()->routeIs('biotime.*')">
                    <flux:sidebar.item icon="signal" :href="route('biotime.index')" :current="request()->routeIs('biotime.index')" wire:navigate>
                        {{ __('BioTime Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="cog-6-tooth" :href="route('biotime.config')" :current="request()->routeIs('biotime.config')" wire:navigate>
                        {{ __('Configuración BioTime') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-path" :href="route('biotime.sync')" :current="request()->routeIs('biotime.sync')" wire:navigate>
                        {{ __('Sincronizar BioTime') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="map-pin" :href="route('biotime.areas')" :current="request()->routeIs('biotime.areas')" wire:navigate>
                        {{ __('Áreas BioTime') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="building-office-2" :href="route('biotime.departments')" :current="request()->routeIs('biotime.departments')" wire:navigate>
                        {{ __('Departamentos BioTime') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="users" :href="route('biotime.employees')" :current="request()->routeIs('biotime.employees')" wire:navigate>
                        {{ __('Empleados BioTime') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                @endcan

                @if(auth()->user()->can('usuarios.view') || auth()->user()->can('roles.view'))
                <flux:sidebar.group expandable heading="Administración" class="grid" :expanded="request()->routeIs('usuarios.*') || request()->routeIs('roles.*')">
                    @can('usuarios.view')
                    <flux:sidebar.item icon="users" :href="route('usuarios.index')" :current="request()->routeIs('usuarios.*')" wire:navigate>
                        {{ __('Usuarios') }}
                    </flux:sidebar.item>
                    @endcan
                    @can('roles.view')
                    <flux:sidebar.item icon="shield-check" :href="route('roles.index')" :current="request()->routeIs('roles.*')" wire:navigate>
                        {{ __('Roles') }}
                    </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
                @endif
            </flux:sidebar.nav>

            <flux:sidebar.spacer />

            <flux:sidebar.nav>
                <div class="px-2 py-2">
                    <livewire:personalization-modal />
                </div>
                <flux:sidebar.item icon="cog-6-tooth" :href="route('profile.edit')" :current="request()->routeIs('profile.*')" wire:navigate>
                    {{ __('Settings') }}
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
                        <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header id="app-header" class="block! {{ $headerAppearanceClass ?? 'dark' }} {{ $headerBgClass ?? 'bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700' }}">
            <flux:navbar class="lg:hidden w-full">
                <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

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
                            <flux:menu.item :href="route('profile.edit')" icon="cog-6-tooth" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                        </flux:menu.radio.group>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full" data-test="logout-button">
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:navbar>

            <flux:navbar scrollable>
                <flux:navbar.item :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>
                    {{ __('Dashboard') }}
                </flux:navbar.item>
                <flux:navbar.item :href="route('checking.index')" :current="request()->routeIs('checking.*')" wire:navigate>
                    {{ __('Checking') }}
                </flux:navbar.item>
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
                    default: 'bg-white lg:bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700',
                    slate: 'bg-white lg:bg-slate-50 dark:bg-slate-950 border-b border-slate-200 dark:border-slate-800',
                    blue: 'bg-white lg:bg-blue-50 dark:bg-blue-950 border-b border-blue-200 dark:border-blue-800',
                    green: 'bg-white lg:bg-green-50 dark:bg-green-950 border-b border-green-200 dark:border-green-800',
                    amber: 'bg-white lg:bg-amber-50 dark:bg-amber-950 border-b border-amber-200 dark:border-amber-800',
                    red: 'bg-white lg:bg-red-50 dark:bg-red-950 border-b border-red-200 dark:border-red-800',
                    violet: 'bg-white lg:bg-violet-50 dark:bg-violet-950 border-b border-violet-200 dark:border-violet-800',
                    indigo: 'bg-white lg:bg-indigo-50 dark:bg-indigo-950 border-b border-indigo-200 dark:border-indigo-800'
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
                    return val === 'system' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : val;
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
                function applyAppearance(params) {
                    var appearance = params.appearance || 'system';
                    var appearanceSidebar = params.appearance_sidebar || 'system';
                    var appearanceHeader = params.appearance_header || 'system';
                    var accent = params.accent || 'neutral';
                    var sidebarBg = params.sidebar_bg || 'default';
                    var headerBg = params.header_bg || 'default';
                    var bodyBg = params.body_bg || 'default';
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
                            return c && !/^bg-\w+(-\d+)?(\/\d+)?$/.test(c) && !/^dark:bg-\w+(-\d+)?(\/\d+)?$/.test(c);
                        });
                        bodyEl.className = (bodyParts.join(' ') + ' ' + (bodyBgClasses[bodyBg] || bodyBgClasses.default)).trim();
                    }
                    try {
                        localStorage.setItem(STORAGE_KEY, JSON.stringify({
                            appearance: appearance,
                            appearance_sidebar: appearanceSidebar,
                            appearance_header: appearanceHeader,
                            accent: accent,
                            sidebar_bg: sidebarBg,
                            header_bg: headerBg,
                            body_bg: bodyBg
                        }));
                    } catch (e) {}
                }
                function getParamsFromDocument() {
                    return {
                        appearance: document.documentElement.getAttribute('data-appearance') || 'system',
                        appearance_sidebar: document.documentElement.getAttribute('data-appearance-sidebar') || 'system',
                        appearance_header: document.documentElement.getAttribute('data-appearance-header') || 'system',
                        accent: document.documentElement.getAttribute('data-accent') || 'neutral',
                        sidebar_bg: document.documentElement.getAttribute('data-sidebar-bg') || 'default',
                        header_bg: document.documentElement.getAttribute('data-header-bg') || 'default',
                        body_bg: document.documentElement.getAttribute('data-body-bg') || 'default'
                    };
                }
                function restoreAppearanceFromStorage() {
                    var stored = getStoredAppearance();
                    if (stored) applyAppearance(stored);
                }
                document.addEventListener('livewire:init', function() {
                    var stored = getStoredAppearance();
                    var params = stored || getParamsFromDocument();
                    applyAppearance(params);
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
