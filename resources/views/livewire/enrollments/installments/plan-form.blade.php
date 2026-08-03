@php
    $saldoPendiente = max(0, (float) ($form['monto_total'] ?? 0) - (float) ($form['cuota_inicial_monto'] ?? 0));
    $sumaCronograma = collect($form['schedule'] ?? [])->sum(fn ($row) => (float) ($row['monto'] ?? 0));
@endphp

<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-700 dark:bg-zinc-800 max-w-5xl">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Crear plan de cuotas</h1>
        <p class="text-sm text-zinc-500">{{ $cliente->nombres }} {{ $cliente->apellidos }} — {{ $clienteMatricula->nombre }}</p>
    </div>

    <form wire:submit.prevent="save" class="space-y-4">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <flux:field>
                <flux:label>Monto total (S/)</flux:label>
                <flux:input type="number" step="0.01" wire:model.live="form.monto_total" required />
            </flux:field>
            <flux:field>
                <flux:label>Cuota inicial (S/)</flux:label>
                <flux:input type="number" step="0.01" min="0" wire:model.live="form.cuota_inicial_monto" required />
            </flux:field>
            <flux:field>
                <flux:label>Número de cuotas</flux:label>
                <flux:input type="number" min="2" max="60" wire:model.live="form.numero_cuotas" required />
            </flux:field>
            <flux:field>
                <flux:label>Frecuencia</flux:label>
                <select wire:model.live="form.frecuencia" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="quincenal">Cada 15 días</option>
                    <option value="mensual">Cada 30 días</option>
                </select>
            </flux:field>
            <flux:field>
                <flux:label>Fecha inicio</flux:label>
                <flux:input type="date" wire:model.live="form.fecha_inicio" required />
            </flux:field>
        </div>

        <div class="grid gap-3 md:grid-cols-3">
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                <p class="text-xs text-zinc-500">Saldo restante</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($saldoPendiente, 2) }}</p>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-900/50">
                <p class="text-xs text-zinc-500">Suma cronograma</p>
                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $sumaCronograma, 2) }}</p>
            </div>
            <div class="rounded-xl border {{ round($saldoPendiente, 2) === round((float) $sumaCronograma, 2) ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30' : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30' }} p-4">
                <p class="text-xs {{ round($saldoPendiente, 2) === round((float) $sumaCronograma, 2) ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-700 dark:text-amber-300' }}">Validación</p>
                <p class="mt-1 text-sm font-semibold {{ round($saldoPendiente, 2) === round((float) $sumaCronograma, 2) ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-800 dark:text-amber-200' }}">
                    {{ round($saldoPendiente, 2) === round((float) $sumaCronograma, 2) ? 'El cronograma cuadra con el saldo pendiente.' : 'La suma del cronograma no coincide con el saldo pendiente.' }}
                </p>
            </div>
        </div>

        <flux:field>
            <flux:label>Observaciones</flux:label>
            <flux:textarea wire:model="form.observaciones" rows="2" />
        </flux:field>

        <div class="flex items-center justify-between">
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Cronograma editable</h2>
            <flux:button type="button" variant="ghost" wire:click="agregarCuotaManual">Agregar cuota</flux:button>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium">#</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Fecha vencimiento</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Monto</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse(($form['schedule'] ?? []) as $index => $row)
                        <tr>
                            <td class="px-3 py-2 text-xs font-semibold">{{ $index + 1 }}</td>
                            <td class="px-3 py-2">
                                <input type="date" wire:model.live="form.schedule.{{ $index }}.fecha_vencimiento" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" min="0.01" wire:model.live="form.schedule.{{ $index }}.monto" class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-right text-sm dark:border-zinc-600 dark:bg-zinc-800" />
                            </td>
                            <td class="px-3 py-2 text-right">
                                <x-ui.table-actions>
                                <flux:button type="button" size="xs" variant="ghost" icon="trash" color="red" wire:click="quitarCuotaManual({{ $index }})" aria-label="Quitar" />
                                </x-ui.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-3 py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">Completa los datos para generar un cronograma.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('clientes.cuotas', ['cliente' => $cliente->id, 'matricula' => $clienteMatricula->id]) }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar plan</flux:button>
        </div>
    </form>
</div>
