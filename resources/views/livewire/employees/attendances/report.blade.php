<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Reporte de asistencia</h1>
        <flux:button variant="ghost" size="xs" href="{{ route('employees.attendances.index') }}" wire:navigate>Volver</flux:button>
    </div>
    <div class="flex gap-4 items-center">
        <select wire:model.live="mes" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-32">
            @for($m = 1; $m <= 12; $m++) <option value="{{ $m }}">{{ now()->month($m)->translatedFormat('F') }}</option> @endfor
        </select>
        <select wire:model.live="anio" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-24">
            @for($y = now()->year; $y >= now()->year - 3; $y--) <option value="{{ $y }}">{{ $y }}</option> @endfor
        </select>
        <select wire:model.live="employeeId" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-48">
            <option value="">Todos</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->nombres }} {{ $e->apellidos }}</option>
            @endforeach
        </select>
    </div>
    <p class="text-sm text-zinc-500">Período: {{ $start->format('d/m/Y') }} - {{ $end->format('d/m/Y') }}</p>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Empleado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Días registrados</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Tardanzas (min)</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($employees as $e)
                    @php $r = $resumen->get($e->id); @endphp
                    <tr>
                        <td class="px-4 py-2">{{ $e->nombre_completo }}</td>
                        <td class="px-4 py-2">{{ $r['dias'] ?? 0 }}</td>
                        <td class="px-4 py-2">{{ $r['tardanza_minutos'] ?? 0 }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
