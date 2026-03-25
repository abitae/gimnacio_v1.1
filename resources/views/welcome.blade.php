<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">

    <title>{{ config('app.name') }}</title>

    <link rel="icon" href="{{ asset('Open9/logo.ico') }}" sizes="any">
    <link rel="apple-touch-icon" href="{{ asset('Open9/logo_completo_sin_fondo.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet">

    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        .welcome-bg {
            background:
                radial-gradient(circle at top left, rgba(34, 197, 94, 0.18), transparent 28%),
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 30%),
                linear-gradient(180deg, #f8fafc 0%, #eff6ff 48%, #f8fafc 100%);
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full welcome-bg text-zinc-800">
    <div class="relative isolate overflow-hidden">
        <div class="absolute inset-x-0 top-0 -z-10 h-80 bg-gradient-to-b from-white/80 to-transparent"></div>

        <header class="w-full px-4 sm:px-6 lg:px-8 py-5">
            <div class="max-w-7xl mx-auto flex items-center justify-between gap-4">
                <a href="{{ url('/') }}" class="flex items-center gap-3 min-w-0">
                    <img src="{{ asset('Open9/logo_completo_sin_fondo.png') }}" alt="{{ config('app.name') }}" class="h-10 w-auto object-contain" />
                    <div class="hidden sm:block">
                        <div class="text-sm font-semibold text-zinc-900">{{ config('app.name') }}</div>
                        <div class="text-xs text-zinc-500">Gestión integral para gimnasios</div>
                    </div>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-3">
                        @auth
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center rounded-xl bg-zinc-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-zinc-800">
                                Ir al panel
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="inline-flex items-center rounded-xl px-4 py-2.5 text-sm font-medium text-zinc-700 transition hover:bg-white/80">
                                Iniciar sesión
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center rounded-xl border border-zinc-200 bg-white px-4 py-2.5 text-sm font-semibold text-zinc-900 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50">
                                    Crear cuenta
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </div>
        </header>

        <main>
            <section class="px-4 pb-14 pt-8 sm:px-6 lg:px-8 lg:pb-20 lg:pt-12">
                <div class="max-w-7xl mx-auto grid gap-12 lg:grid-cols-[1.15fr_0.85fr] lg:items-center">
                    <div>
                        <span class="inline-flex items-center rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700">
                            Operación diaria + control comercial
                        </span>

                        <h1 class="mt-6 max-w-3xl text-4xl font-bold tracking-tight text-zinc-950 sm:text-5xl lg:text-6xl">
                            Administra tu gimnasio con una vista clara de clientes, cobros y operación.
                        </h1>

                        <p class="mt-5 max-w-2xl text-lg leading-8 text-zinc-600">
                            {{ config('app.name') }} centraliza membresías, matrículas, clases, caja, CRM, nutrición y reportes para que todo el equipo trabaje con una sola fuente de verdad.
                        </p>

                        <div class="mt-8 flex flex-wrap gap-3">
                            @if (Route::has('login') && ! auth()->check())
                                <a href="{{ route('login') }}" class="inline-flex items-center rounded-2xl bg-zinc-900 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-zinc-900/10 transition hover:bg-zinc-800">
                                    Acceder al sistema
                                </a>
                                @if (Route::has('register'))
                                    <a href="{{ route('register') }}" class="inline-flex items-center rounded-2xl border border-zinc-200 bg-white px-6 py-3.5 text-sm font-semibold text-zinc-900 shadow-sm transition hover:bg-zinc-50">
                                        Solicitar acceso
                                    </a>
                                @endif
                            @else
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center rounded-2xl bg-zinc-900 px-6 py-3.5 text-sm font-semibold text-white shadow-lg shadow-zinc-900/10 transition hover:bg-zinc-800">
                                    Entrar al panel
                                </a>
                            @endif
                            <a href="#modulos" class="inline-flex items-center rounded-2xl px-6 py-3.5 text-sm font-semibold text-zinc-700 transition hover:bg-white/70">
                                Ver módulos
                            </a>
                        </div>

                        <dl class="mt-10 grid gap-4 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/70 bg-white/75 p-5 shadow-sm backdrop-blur">
                                <dt class="text-sm text-zinc-500">Cobranza</dt>
                                <dd class="mt-2 text-2xl font-bold text-zinc-950">Cuotas, caja y POS</dd>
                            </div>
                            <div class="rounded-2xl border border-white/70 bg-white/75 p-5 shadow-sm backdrop-blur">
                                <dt class="text-sm text-zinc-500">Clientes</dt>
                                <dd class="mt-2 text-2xl font-bold text-zinc-950">Perfil 360</dd>
                            </div>
                            <div class="rounded-2xl border border-white/70 bg-white/75 p-5 shadow-sm backdrop-blur">
                                <dt class="text-sm text-zinc-500">Analítica</dt>
                                <dd class="mt-2 text-2xl font-bold text-zinc-950">Reportes accionables</dd>
                            </div>
                        </dl>
                    </div>

                    <div class="relative">
                        <div class="rounded-[28px] border border-white/70 bg-white/85 p-4 shadow-2xl shadow-sky-950/10 backdrop-blur">
                            <div class="rounded-[24px] bg-zinc-950 p-5 text-white">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-xs uppercase tracking-[0.2em] text-zinc-400">Resumen operativo</div>
                                        <div class="mt-2 text-2xl font-semibold">Dashboard diario</div>
                                    </div>
                                    <div class="rounded-2xl bg-white/10 px-3 py-2 text-right">
                                        <div class="text-xs text-zinc-400">Estado</div>
                                        <div class="text-sm font-semibold text-emerald-300">Todo centralizado</div>
                                    </div>
                                </div>

                                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                                        <div class="text-xs text-zinc-400">Clientes activos</div>
                                        <div class="mt-2 text-3xl font-bold">248</div>
                                        <div class="mt-1 text-sm text-emerald-300">+12 este mes</div>
                                    </div>
                                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                                        <div class="text-xs text-zinc-400">Cobros pendientes</div>
                                        <div class="mt-2 text-3xl font-bold">36</div>
                                        <div class="mt-1 text-sm text-amber-300">Seguimiento activo</div>
                                    </div>
                                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                                        <div class="text-xs text-zinc-400">Caja del día</div>
                                        <div class="mt-2 text-3xl font-bold">S/ 2,840</div>
                                        <div class="mt-1 text-sm text-sky-300">Ingresos registrados</div>
                                    </div>
                                    <div class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                                        <div class="text-xs text-zinc-400">CRM</div>
                                        <div class="mt-2 text-3xl font-bold">18</div>
                                        <div class="mt-1 text-sm text-violet-300">Leads por convertir</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                                    <div class="text-sm font-semibold text-zinc-900">Flujos conectados</div>
                                    <ul class="mt-3 space-y-2 text-sm text-zinc-600">
                                        <li>Clientes, membresías y matrículas</li>
                                        <li>Cuotas, pagos y caja</li>
                                        <li>Nutrición, rutinas y seguimiento</li>
                                    </ul>
                                </div>
                                <div class="rounded-2xl border border-zinc-200 bg-white p-4">
                                    <div class="text-sm font-semibold text-zinc-900">Equipo alineado</div>
                                    <ul class="mt-3 space-y-2 text-sm text-zinc-600">
                                        <li>Permisos por rol</li>
                                        <li>Navegación consistente</li>
                                        <li>Reportes listos para gestión</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section id="modulos" class="px-4 py-14 sm:px-6 lg:px-8">
                <div class="max-w-7xl mx-auto">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-bold tracking-tight text-zinc-950">Módulos clave del sistema</h2>
                        <p class="mt-3 text-base leading-7 text-zinc-600">
                            Diseñado para que cada área trabaje con consistencia lógica, permisos claros y datos conectados.
                        </p>
                    </div>

                    <div class="mt-8 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                        @foreach ([
                            ['title' => 'Clientes', 'desc' => 'Ficha completa, salud, historial comercial y contexto operativo.'],
                            ['title' => 'Operación diaria', 'desc' => 'Checking, caja, POS y cobranza desde un mismo flujo.'],
                            ['title' => 'Bienestar', 'desc' => 'Nutrición, objetivos, rutinas y seguimiento del cliente.'],
                            ['title' => 'Comercial y analítica', 'desc' => 'CRM, campañas, cupones y reportes para la toma de decisiones.'],
                        ] as $module)
                            <article class="rounded-3xl border border-white/80 bg-white/80 p-6 shadow-sm backdrop-blur transition hover:-translate-y-0.5 hover:shadow-md">
                                <h3 class="text-lg font-semibold text-zinc-950">{{ $module['title'] }}</h3>
                                <p class="mt-3 text-sm leading-6 text-zinc-600">{{ $module['desc'] }}</p>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section class="px-4 pb-16 sm:px-6 lg:px-8">
                <div class="max-w-7xl mx-auto">
                    <div class="rounded-[32px] border border-zinc-200 bg-white px-6 py-8 shadow-sm sm:px-8 lg:flex lg:items-center lg:justify-between">
                        <div class="max-w-2xl">
                            <h2 class="text-2xl font-bold tracking-tight text-zinc-950">Todo el gimnasio en un solo sistema</h2>
                            <p class="mt-3 text-zinc-600">
                                Menos hojas sueltas, menos duplicidad y más control sobre clientes, ventas, seguimiento y resultados.
                            </p>
                        </div>
                        <div class="mt-6 flex flex-wrap gap-3 lg:mt-0">
                            @if (Route::has('login') && ! auth()->check())
                                <a href="{{ route('login') }}" class="inline-flex items-center rounded-2xl bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800">
                                    Entrar ahora
                                </a>
                            @else
                                <a href="{{ url('/dashboard') }}" class="inline-flex items-center rounded-2xl bg-zinc-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-zinc-800">
                                    Abrir dashboard
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="border-t border-white/60 px-4 py-6 text-center text-sm text-zinc-500 sm:px-6 lg:px-8">
            &copy; {{ date('Y') }} {{ config('app.name') }}. Plataforma de gestión para gimnasios y centros de entrenamiento.
        </footer>
    </div>
</body>
</html>
