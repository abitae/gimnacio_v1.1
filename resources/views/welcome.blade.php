<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
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
        .bg-gym { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 50%, #f0fdf4 100%); }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-gym text-zinc-800 flex flex-col">
    {{-- Header --}}
    <header class="w-full px-4 sm:px-6 lg:px-8 py-4">
        <div class="max-w-5xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('Open9/logo_completo_sin_fondo.png') }}" alt="{{ config('app.name') }}" class="h-9 object-contain" />
            </a>
            @if (Route::has('login'))
                <nav class="flex items-center gap-3">
                    @auth
                        <a href="{{ url('/dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-zinc-800 text-white text-sm font-medium hover:bg-zinc-700 transition-colors">
                            Panel
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 rounded-lg text-zinc-700 text-sm font-medium hover:bg-white/80 hover:shadow-sm transition-all">
                            Iniciar sesión
                        </a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-zinc-800 text-white text-sm font-medium hover:bg-zinc-700 transition-colors">
                                Registrarse
                            </a>
                        @endif
                    @endauth
                </nav>
            @endif
        </div>
    </header>

    {{-- Hero --}}
    <main class="flex-1 flex items-center justify-center px-4 sm:px-6 lg:px-8 py-12 lg:py-16">
        <div class="max-w-5xl w-full">
            <div class="grid lg:grid-cols-2 gap-10 lg:gap-16 items-center">
                <div>
                    <h1 class="text-3xl sm:text-4xl lg:text-5xl font-bold text-zinc-800 leading-tight tracking-tight">
                        Gestiona tu gimnasio de forma simple
                    </h1>
                    <p class="mt-4 text-lg text-zinc-600 leading-relaxed max-w-lg">
                        Control de miembros, clases, horarios y ventas en un solo lugar. {{ config('app.name') }} te ayuda a enfocarte en lo que importa: tu comunidad.
                    </p>
                    <ul class="mt-8 space-y-3 text-zinc-700">
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            Membresías y planes
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            Clases y entrenadores
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            Caja y punto de venta
                        </li>
                        <li class="flex items-center gap-3">
                            <span class="flex-shrink-0 w-6 h-6 rounded-full bg-emerald-100 flex items-center justify-center">
                                <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            Asistencia y control de acceso
                        </li>
                    </ul>
                    @if (Route::has('login') && !auth()->check())
                        <div class="mt-10 flex flex-wrap gap-3">
                            <a href="{{ route('login') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-zinc-800 text-white font-medium hover:bg-zinc-700 shadow-sm transition-colors">
                                Acceder al sistema
                            </a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-white text-zinc-800 font-medium border border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50 transition-colors">
                                    Crear cuenta
                                </a>
                            @endif
                        </div>
                    @elseif(auth()->check())
                        <div class="mt-10">
                            <a href="{{ url('/dashboard') }}" class="inline-flex items-center px-6 py-3 rounded-xl bg-zinc-800 text-white font-medium hover:bg-zinc-700 shadow-sm transition-colors">
                                Ir al panel
                            </a>
                        </div>
                    @endif
                </div>
                <div class="flex justify-center lg:justify-end">
                    <div class="relative w-full max-w-sm aspect-square rounded-2xl bg-white/80 shadow-xl border border-white/60 flex items-center justify-center p-8">
                        <img src="{{ asset('Open9/logo_completo_sin_fondo.png') }}" alt="{{ config('app.name') }}" class="w-full max-w-[240px] object-contain" />
                    </div>
                </div>
            </div>
        </div>
    </main>

    {{-- Footer --}}
    <footer class="py-6 text-center text-sm text-zinc-500">
        &copy; {{ date('Y') }} {{ config('app.name') }}. Sistema de gestión para gimnasios.
    </footer>
</body>
</html>
