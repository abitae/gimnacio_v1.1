<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between flex-wrap gap-2">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Calendario de alquileres</h1>
        <div class="flex gap-2 items-center">
            <flux:input type="date" wire:model.live="fecha" />
            <select wire:model.live="spaceId" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-48">
                <option value="">Todos los espacios</option>
                @foreach($spaces as $s)
                    <option value="{{ $s->id }}">{{ $s->nombre }}</option>
                @endforeach
            </select>
            @can('rentals.create')
            <flux:button size="xs" href="{{ route('rentals.bookings.create', ['fecha' => $fechaCarbon->format('Y-m-d')]) }}" wire:navigate>Nueva reserva</flux:button>
            @endcan
        </div>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Espacio</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Hora</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Cliente / Externo</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Precio</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($rentals as $r)
                    <tr>
                        <td class="px-4 py-2">{{ $r->rentableSpace->nombre }}</td>
                        <td class="px-4 py-2">{{ \Carbon\Carbon::parse($r->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($r->hora_fin)->format('H:i') }}</td>
                        <td class="px-4 py-2">{{ $r->cliente ? $r->cliente->nombres . ' ' . $r->cliente->apellidos : ($r->nombre_externo ?? '—') }}</td>
                        <td class="px-4 py-2">S/ {{ number_format($r->precio, 2) }}</td>
                        <td class="px-4 py-2"><span class="rounded-full px-1.5 py-0.5 text-xs bg-zinc-100 dark:bg-zinc-700">{{ \App\Models\Core\Rental::ESTADOS[$r->estado] ?? $r->estado }}</span></td>
                        <td class="px-4 py-2">
                            <flux:button size="xs" variant="ghost" href="{{ route('rentals.bookings.show', $r) }}" wire:navigate>Ver</flux:button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-500">Sin reservas para esta fecha</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
