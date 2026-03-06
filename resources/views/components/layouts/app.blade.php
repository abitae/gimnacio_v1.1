<x-layouts.app.sidebar :title="$title ?? null">
    <flux:main>
        <x-breadcrumbs />
        {{ $slot }}
    </flux:main>
</x-layouts.app.sidebar>
