<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Asistencia de personal</h1>
        @can('attendance.create')
        <flux:button size="xs" href="{{ route('employees.attendances.create') }}" wire:navigate>Registrar asistencia</flux:button>
        @endcan
    </div>
    <div class="flex gap-2 items-center">
        <flux:input type="date" wire:model.live="fecha" />
        <select wire:model.live="employeeId" class="rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-2 py-1.5 text-sm w-48">
            <option value="">Todos</option>
            @foreach($employees as $e)
                <option value="{{ $e->id }}">{{ $e->nombres }} {{ $e->apellidos }}</option>
            @endforeach
        </select>
        <flux:button size="xs" variant="ghost" href="{{ route('employees.attendances.report') }}" wire:navigate>Reporte</flux:button>
    </div>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Empleado</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Ingreso</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Salida</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($attendances as $a)
                    <tr>
                        <td class="px-4 py-2">{{ $a->employee->nombre_completo }}</td>
                        <td class="px-4 py-2">{{ $a->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $a->hora_ingreso ? \Carbon\Carbon::parse($a->hora_ingreso)->format('H:i') : '—' }}</td>
                        <td class="px-4 py-2">{{ $a->hora_salida ? \Carbon\Carbon::parse($a->hora_salida)->format('H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-zinc-500">No hay registros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $attendances->links() }}
</div>
