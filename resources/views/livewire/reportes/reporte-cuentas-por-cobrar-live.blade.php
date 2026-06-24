<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cuentas por cobrar</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Vista analítica de deudas pendientes. Para registrar cobros use Operaciones → Cobros pendientes.</p>
        </div>
        @if ($puedeCobrarOperativo)
            <a href="{{ route('pos.cuentas-por-cobrar') }}" wire:navigate
                class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-medium text-white hover:bg-red-700">
                <flux:icon name="banknotes" class="size-4" />
                Ir a cobros operativos
            </a>
        @endif
    </div>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Saldo total</div>
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($summary['total_saldo'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-3 dark:border-zinc-600 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Registros</div>
            <div class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['total_registros'] }}</div>
        </div>
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 dark:border-rose-900 dark:bg-rose-950/30">
            <div class="text-xs text-rose-600 dark:text-rose-400">Vencido</div>
            <div class="text-lg font-semibold text-rose-700 dark:text-rose-300">S/ {{ number_format($summary['total_vencido'], 2) }}</div>
        </div>
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-900 dark:bg-amber-950/30">
            <div class="text-xs text-amber-700 dark:text-amber-400">Por vencer</div>
            <div class="text-lg font-semibold text-amber-800 dark:text-amber-300">S/ {{ number_format($summary['total_pendiente'], 2) }}</div>
        </div>
    </div>

    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
        <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="DNI, código, celular o nombre" />
        <flux:input type="date" size="xs" wire:model.live="fechaInicio" />
        <flux:input type="date" size="xs" wire:model.live="fechaFin" />
        <flux:select size="xs" wire:model.live="estadoFilter">
            <option value="">Todos los estados</option>
            <option value="pendiente">Pendiente</option>
            <option value="parcial">Parcial</option>
            <option value="vencido">Vencido</option>
        </flux:select>
        <flux:select size="xs" wire:model.live="perPage">
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Documento</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Saldo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Origen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($debts as $debt)
                    <tr wire:key="debt-report-{{ $debt->id }}">
                        <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">
                            {{ $debt->cliente?->nombres }} {{ $debt->cliente?->apellidos }}
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $debt->cliente?->numero_documento }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $debt->fecha_registro?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3 text-right text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $debt->monto_total, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-zinc-900 dark:text-zinc-100">S/ {{ number_format((float) $debt->saldo_pendiente, 2) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                @if($debt->estado === 'vencido') bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-300
                                @elseif($debt->estado === 'parcial') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                                @else bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200 @endif">
                                {{ ucfirst($debt->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">{{ $debt->operationalOriginLabel() }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">No hay deudas pendientes con los filtros aplicados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $debts->links() }}</div>
</div>
