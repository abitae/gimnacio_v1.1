<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>403 - Acceso denegado | {{ config('app.name') }}</title>
        <link rel="icon" href="{{ asset('Open9/logo.ico') }}" sizes="any">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
        @vite(['resources/css/app.css'])
        <style>
            .error-page { min-height: 100vh; display: flex; align-items: center; justify-content: center; background: rgb(39 39 42); color: rgb(244 244 245); }
            .error-page a { color: rgb(168 85 247); text-decoration: none; font-weight: 500; }
            .error-page a:hover { text-decoration: underline; }
        </style>
    </head>
    <body class="antialiased">
        <div class="error-page">
            <div class="max-w-md mx-auto px-6 text-center">
                <p class="text-6xl font-bold text-zinc-500">403</p>
                <h1 class="mt-4 text-xl font-semibold text-zinc-100">Acceso denegado</h1>
                <p class="mt-2 text-sm text-zinc-400">No tienes permiso para acceder a esta página.</p>
                <p class="mt-6">
                    <a href="{{ route('dashboard') }}">Volver al inicio</a>
                </p>
            </div>
        </div>
    </body>
</html>
