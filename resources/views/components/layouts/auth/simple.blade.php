<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="light">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased">
        <div class="bg-background flex min-h-svh flex-col items-center justify-center gap-6 p-6 md:p-10">
            <div class="flex w-full max-w-sm flex-col gap-2">
                <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                    @if (!empty($appBrandLogoUrl))
                        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name', 'Firnetness') }}" class="h-9 object-contain mb-1" />
                    @else
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-900 text-lg font-bold tracking-[0.22em] text-white">F</span>
                    @endif
                    <span class="sr-only">{{ $appBrandName ?? config('app.name', 'Firnetness') }}</span>
                </a>
                <div class="flex flex-col gap-6">
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
