<div class="space-y-6 p-4">
    <flux:heading size="lg">{{ __('Bandeja operativa de alquileres') }}</flux:heading>
    <flux:text class="text-sm text-zinc-500">{{ __('Reservas de hoy y próximas 48 horas') }}</flux:text>

    <div class="grid gap-4 md:grid-cols-3">
        <flux:card class="p-4">
            <flux:subheading>{{ __('Hoy') }}</flux:subheading>
            <flux:heading size="xl">{{ $hoyReservas->count() }}</flux:heading>
        </flux:card>
        <flux:card class="p-4">
            <flux:subheading>{{ __('Pendientes confirmación') }}</flux:subheading>
            <flux:heading size="xl">{{ $pendientesConfirmacion }}</flux:heading>
        </flux:card>
        <flux:card class="p-4">
            <flux:subheading>{{ __('Próximas 48h') }}</flux:subheading>
            <flux:heading size="xl">{{ $proximas->count() }}</flux:heading>
        </flux:card>
    </div>

    <flux:card class="overflow-hidden">
        <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-800">
            <flux:subheading>{{ __('Reservas de hoy') }}</flux:subheading>
        </div>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-800">
            @forelse ($hoyReservas as $rental)
                <div class="flex flex-wrap items-center justify-between gap-2 px-4 py-3 text-sm">
                    <div>
                        <span class="font-medium">{{ $rental->rentableSpace?->nombre ?? '—' }}</span>
                        <span class="text-zinc-500"> · {{ $rental->cliente?->nombres ?? $rental->nombre_externo ?? '—' }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:badge size="sm">{{ $rental->hora_inicio?->format('H:i') ?? $rental->hora_inicio }} – {{ $rental->hora_fin?->format('H:i') ?? $rental->hora_fin }}</flux:badge>
                        <flux:badge color="zinc" size="sm">{{ ucfirst($rental->estado) }}</flux:badge>
                        <flux:button href="{{ route('rentals.bookings.show', $rental) }}" wire:navigate size="xs" variant="ghost">{{ __('Ver') }}</flux:button>
                    </div>
                </div>
            @empty
                <flux:text class="p-4 text-zinc-500">{{ __('Sin reservas para hoy.') }}</flux:text>
            @endforelse
        </div>
    </flux:card>
</div>
