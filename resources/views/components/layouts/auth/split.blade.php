<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta name="color-scheme" content="light dark">
    @include('partials.head')
</head>

<body class="min-h-screen bg-zinc-100 antialiased dark:bg-zinc-900">
    <div
        class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <div
            class="relative hidden h-full flex-col justify-between p-10 text-white lg:flex lg:border-e lg:border-zinc-200 lg:dark:border-zinc-700 bg-no-repeat bg-center"
            style="background-image: url('{{ asset('Open9/b2-ejercitando.jpg') }}'); background-size: 100% 100%;">
            <div class="absolute inset-0 bg-black/50 dark:bg-black/60" aria-hidden="true"></div>

            

            @php
                [$message, $author] = str(Illuminate\Foundation\Inspiring::quotes()->random())->explode('-');
            @endphp

            <div class="relative z-20 mt-auto">
                <blockquote class="space-y-2 drop-shadow-sm">
                    <flux:heading size="lg">&ldquo;{{ trim($message) }}&rdquo;</flux:heading>
                    <footer>
                        <flux:heading>{{ trim($author) }}</flux:heading>
                    </footer>
                </blockquote>
            </div>
        </div>
        <div class="w-full lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium"
                    wire:navigate>
                    @if (!empty($appBrandLogoUrl))
                        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name', 'Firnetness') }}"
                            class="h-9 object-contain" />
                    @else
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-zinc-900 text-lg font-bold tracking-[0.22em] text-white">F</span>
                    @endif
                    <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $appBrandName ?? config('app.name', 'Firnetness') }}</span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </div>
    @fluxScripts
</body>

</html>
