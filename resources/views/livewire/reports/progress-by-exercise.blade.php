<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Progreso por ejercicio</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Último peso, mejor peso y tendencia por ejercicio (cliente por DNI/CE)</p>
    </div>

    <div class="flex flex-wrap gap-2 items-end">
        <flux:field>
            <flux:label>Tipo documento</flux:label>
            <flux:select wire:model="tipo_documento">
                <option value="DNI">DNI</option>
                <option value="CE">CE</option>
            </flux:select>
        </flux:field>
        <flux:field>
            <flux:label>Número documento</flux:label>
            <flux:input wire:model="numero_documento" placeholder="Ej. 12345678" />
        </flux:field>
        <flux:button type="button" variant="primary" size="sm" wire:click="buscarCliente" wire:loading.attr="disabled">Buscar</flux:button>
    </div>

    @if(session('error'))
        <p class="text-sm text-red-600 dark:text-red-400">{{ session('error') }}</p>
    @endif

    @if($cliente)
        <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Ejercicio</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Último peso (kg)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Mejor peso (kg)</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tendencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($progress as $row)
                        <tr class="bg-white dark:bg-zinc-800">
                            <td class="px-4 py-2">{{ $row['exercise_nombre'] }}</td>
                            <td class="px-4 py-2">{{ $row['ultimo_peso'] !== null ? number_format($row['ultimo_peso'], 1) : '—' }}</td>
                            <td class="px-4 py-2">{{ $row['mejor_peso'] !== null ? number_format($row['mejor_peso'], 1) : '—' }}</td>
                            <td class="px-4 py-2">{{ $row['tendencia'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-zinc-500 dark:text-zinc-400">No hay datos de progreso para este cliente.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
