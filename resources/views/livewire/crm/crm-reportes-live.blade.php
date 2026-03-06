<div class="space-y-4">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Reportes CRM</h1>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">Conversión, por asesor y por canal</p>
    </div>

    <div class="flex flex-wrap gap-2 items-center">
        <flux:label>Desde</flux:label>
        <flux:input type="date" wire:model.live="from" />
        <flux:label class="ml-2">Hasta</flux:label>
        <flux:input type="date" wire:model.live="to" />
        <div class="flex gap-1 ml-2" role="tablist" aria-label="Reportes CRM">
            <flux:button size="xs" variant="{{ $tab === 'conversion' ? 'primary' : 'ghost' }}" wire:click="$set('tab', 'conversion')" role="tab" aria-selected="{{ $tab === 'conversion' ? 'true' : 'false' }}">Conversión</flux:button>
            <flux:button size="xs" variant="{{ $tab === 'advisor' ? 'primary' : 'ghost' }}" wire:click="$set('tab', 'advisor')" role="tab" aria-selected="{{ $tab === 'advisor' ? 'true' : 'false' }}">Por asesor</flux:button>
            <flux:button size="xs" variant="{{ $tab === 'channel' ? 'primary' : 'ghost' }}" wire:click="$set('tab', 'channel')" role="tab" aria-selected="{{ $tab === 'channel' ? 'true' : 'false' }}">Por canal</flux:button>
        </div>
    </div>

    @if($tab === 'conversion')
    @php $data = $this->conversionData; @endphp
    @if($data['total_leads'] === 0 && $data['convertidos'] === 0)
    <p class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 text-sm text-zinc-500 dark:text-zinc-400">Sin datos en el período seleccionado.</p>
    @else
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Total leads</p>
            <p class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $data['total_leads'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Contactados</p>
            <p class="text-2xl font-semibold">{{ $data['contactados'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Convertidos</p>
            <p class="text-2xl font-semibold text-green-600 dark:text-green-400">{{ $data['convertidos'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
            <p class="text-xs text-zinc-500 dark:text-zinc-400">Tasa conversión</p>
            <p class="text-2xl font-semibold">{{ $data['tasa_conversion'] }}%</p>
        </div>
    </div>
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
        <p class="text-sm text-zinc-500">Tiempo promedio de cierre: <strong>{{ $data['tiempo_promedio_cierre_dias'] ?? '—' }}</strong> días</p>
    </div>
    @endif
    @endif

    @if($tab === 'advisor')
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Asesor</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Leads</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Actividades</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Tareas hechas</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Tareas vencidas</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Ventas ganadas</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Monto S/</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($this->byAdvisorData as $row)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $row['user_name'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $row['leads_count'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $row['activities_count'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $row['tasks_done'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $row['tasks_overdue'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $row['deals_won_count'] }}</td>
                    <td class="px-4 py-2 text-right">{{ number_format($row['monto_ventas'], 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-6 text-center text-zinc-500">Sin datos en el período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif

    @if($tab === 'channel')
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Canal</th>
                    <th class="px-4 py-2 text-right font-medium text-zinc-500">Total leads</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($this->byChannelData as $row)
                <tr>
                    <td class="px-4 py-2 font-medium">{{ $row['canal'] }}</td>
                    <td class="px-4 py-2 text-right">{{ $row['total'] }}</td>
                </tr>
                @empty
                <tr><td colspan="2" class="px-4 py-6 text-center text-zinc-500">Sin datos en el período seleccionado.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @endif
</div>
