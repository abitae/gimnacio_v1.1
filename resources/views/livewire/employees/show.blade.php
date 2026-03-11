<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $employee->nombre_completo }}</h1>
        <flux:button variant="ghost" size="xs" href="{{ route('employees.index') }}" wire:navigate>Volver</flux:button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><span class="text-zinc-500">Documento</span><p class="font-medium">{{ $employee->documento }}</p></div>
        <div><span class="text-zinc-500">Cargo</span><p class="font-medium">{{ $employee->cargo ?? '—' }}</p></div>
        <div><span class="text-zinc-500">Estado</span><p class="font-medium">{{ ucfirst($employee->estado) }}</p></div>
    </div>
    <h2 class="text-sm font-medium">Asistencia reciente</h2>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Ingreso</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-zinc-700 dark:text-zinc-300">Salida</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($attendances as $a)
                    <tr>
                        <td class="px-4 py-2">{{ $a->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">{{ $a->hora_ingreso ? \Carbon\Carbon::parse($a->hora_ingreso)->format('H:i') : '—' }}</td>
                        <td class="px-4 py-2">{{ $a->hora_salida ? \Carbon\Carbon::parse($a->hora_salida)->format('H:i') : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-6 text-center text-zinc-500">Sin registros</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $attendances->links() }}
</div>
