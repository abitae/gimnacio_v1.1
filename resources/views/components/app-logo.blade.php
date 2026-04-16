<a href="{{ route('dashboard') }}" class="flex items-center gap-2" wire:navigate>
    @if (!empty($appBrandLogoUrl))
        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name', 'Firnetness') }}" class="h-8 object-contain" />
    @else
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl bg-zinc-900 text-xs font-bold tracking-[0.2em] text-white dark:bg-white dark:text-zinc-900">F</span>
        <span class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $appBrandName ?? config('app.name', 'Firnetness') }}</span>
    @endif
</a>
