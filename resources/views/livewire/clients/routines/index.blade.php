<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Rutinas de {{ $cliente->nombres }} {{ $cliente->apellidos }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $cliente->tipo_documento }} {{ $cliente->numero_documento }}</p>
        </div>
        <flux:button href="{{ route('clientes.rutinas.asignar') }}" variant="ghost" size="xs" wire:navigate>Asignar otra rutina</flux:button>
    </div>

    <div class="space-y-2">
        @foreach($cliente->clientRoutines as $routine)
            <flux:card class="p-3 {{ $routine->estado === 'activa' ? 'ring-1 ring-green-500/30' : '' }}">
                <div class="flex items-center justify-between flex-wrap gap-2">
                    <div>
                        <a href="{{ route('clientes.rutinas.show', [$cliente, $routine]) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $routine->routineTemplate?->nombre ?? 'Rutina' }}</a>
                        <span class="ml-2 text-xs {{ $routine->estado === 'activa' ? 'text-green-600 dark:text-green-400' : 'text-zinc-500' }}">{{ $routine->estado_label }}</span>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">Desde {{ $routine->fecha_inicio->format('d/m/Y') }} · Entrenador: {{ $routine->trainer?->name ?? '—' }}</p>
                    </div>
                    <div class="flex gap-1">
                        @if($routine->estado === 'activa')
                            <flux:button size="xs" variant="ghost" wire:click="pausar({{ $routine->id }})" wire:confirm="¿Pausar esta rutina?">Pausar</flux:button>
                            <flux:button size="xs" variant="ghost" wire:click="finalizar({{ $routine->id }})" wire:confirm="¿Finalizar esta rutina?">Finalizar</flux:button>
                        @endif
                        <flux:button href="{{ route('clientes.rutinas.show', [$cliente, $routine]) }}" size="xs" variant="ghost" wire:navigate>Ver</flux:button>
                        <flux:button href="{{ route('clientes.rutinas.sesiones.index', [$cliente, $routine]) }}" size="xs" variant="ghost" wire:navigate>Sesiones</flux:button>
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>
    @if($cliente->clientRoutines->isEmpty())
        <p class="text-zinc-500 dark:text-zinc-400">Este cliente no tiene rutinas asignadas. <a href="{{ route('clientes.rutinas.asignar') }}" wire:navigate class="text-blue-600 dark:text-blue-400 hover:underline">Asignar rutina</a></p>
    @endif
</div>
