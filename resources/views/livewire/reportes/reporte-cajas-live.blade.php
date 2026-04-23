<div class="space-y-5 rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800/50 overflow-hidden">
    <div class="bg-gradient-to-r from-emerald-600 to-emerald-700 px-5 py-4 text-white">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-bold">Reporte de cajas</h1>
                <p class="text-sm text-emerald-100">Filtro por sucursal, usuario y fechas con detalle completo de movimientos.</p>
            </div>
            <div class="flex gap-2 print:hidden">
                <x-reportes.exportar-buttons tipo="cajas" :fechaDesde="$fechaDesde" :fechaHasta="$fechaHasta" />
                <button type="button" onclick="window.print()" class="rounded-xl border border-white/30 bg-white/10 px-4 py-2 text-sm font-semibold hover:bg-white/20">
                    Imprimir
                </button>
            </div>
        </div>
    </div>

    <div class="space-y-4 px-5 pb-5">
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4 print:hidden">
            <flux:input type="date" size="xs" wire:model.live="fechaDesde" label="Fecha inicio" />
            <flux:input type="date" size="xs" wire:model.live="fechaHasta" label="Fecha fin" />
            <flux:select size="xs" wire:model.live="sucursalId" label="Sucursal">
                <option value="">Todas</option>
                @foreach ($sucursales as $sucursal)
                    <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                @endforeach
            </flux:select>
            <flux:select size="xs" wire:model.live="usuarioId" label="Usuario">
                <option value="">Todos</option>
                @foreach ($usuarios as $usuario)
                    <option value="{{ $usuario->id }}">{{ $usuario->name }}</option>
                @endforeach
            </flux:select>
        </div>

        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-6">
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Cajas</div>
                <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">{{ $resumen['cantidad'] }}</div>
            </div>
            <div class="rounded-xl border border-green-100 bg-green-50/60 p-4 dark:border-green-900/50 dark:bg-green-950/30">
                <div class="text-xs font-medium text-green-600 dark:text-green-400">Abiertas</div>
                <div class="text-lg font-bold text-green-700 dark:text-green-300">{{ $resumen['abiertas'] }}</div>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Cerradas</div>
                <div class="text-lg font-bold text-zinc-900 dark:text-zinc-100">{{ $resumen['cerradas'] }}</div>
            </div>
            <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <div class="text-xs font-medium text-emerald-600 dark:text-emerald-400">Total ingresos</div>
                <div class="text-lg font-bold text-emerald-700 dark:text-emerald-300">S/ {{ number_format((float) $resumen['total_ingresos'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-red-100 bg-red-50/60 p-4 dark:border-red-900/50 dark:bg-red-950/30">
                <div class="text-xs font-medium text-red-600 dark:text-red-400">Total salidas</div>
                <div class="text-lg font-bold text-red-700 dark:text-red-300">S/ {{ number_format((float) $resumen['total_salidas'], 2) }}</div>
            </div>
            <div class="rounded-xl border border-sky-100 bg-sky-50/60 p-4 dark:border-sky-900/50 dark:bg-sky-950/30">
                <div class="text-xs font-medium text-sky-600 dark:text-sky-400">Total vendido</div>
                <div class="text-lg font-bold text-sky-700 dark:text-sky-300">S/ {{ number_format((float) ($resumen['total_vendido'] ?? 0), 2) }}</div>
            </div>
        </div>

        <div class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Totales por método de pago</h2>
                <div class="space-y-2">
                    @forelse(($resumen['por_metodo_pago'] ?? []) as $metodo => $monto)
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900/50">
                            <span>{{ $metodo ?: 'Sin método' }}</span>
                            <span class="font-semibold">S/ {{ number_format((float) $monto, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Sin movimientos por método de pago.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <h2 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Totales por usuario</h2>
                <div class="space-y-2">
                    @forelse(($resumen['por_usuario'] ?? []) as $usuario => $monto)
                        <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3 py-2 text-sm dark:bg-zinc-900/50">
                            <span>{{ $usuario }}</span>
                            <span class="font-semibold">S/ {{ number_format((float) $monto, 2) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Sin movimientos agrupados por usuario.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium">Caja</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Usuario</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Sucursal</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Apertura</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Cierre</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Estado</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Inicial</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Final</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($cajas as $c)
                        <tr>
                            <td class="px-3 py-2 text-xs font-semibold">#{{ $c->id }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->usuario?->name ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->sucursal?->nombre ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->fecha_apertura?->format('d/m/Y H:i') }}</td>
                            <td class="px-3 py-2 text-xs">{{ $c->fecha_cierre?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs capitalize">{{ $c->estado }}</td>
                            <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) $c->saldo_inicial, 2) }}</td>
                            <td class="px-3 py-2 text-right text-xs">S/ {{ number_format((float) ($c->saldo_final ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-4 text-center text-zinc-500">No hay cajas en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="border-b border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-semibold text-zinc-900 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                Detalle de movimientos
            </div>
            <table class="w-full text-sm">
                <thead class="bg-white dark:bg-zinc-950">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium">Fecha</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Caja</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Usuario</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Sucursal</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Concepto</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Método</th>
                        <th class="px-3 py-2 text-left text-xs font-medium">Operación</th>
                        <th class="px-3 py-2 text-right text-xs font-medium">Monto</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($detalleMovimientos as $movimiento)
                        <tr>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['fecha']?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">#{{ $movimiento['caja_id'] }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['usuario_caja'] ?? $movimiento['usuario'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['sucursal_caja'] ?? $movimiento['sucursal'] ?? '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['concepto'] }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['metodo_pago'] ?: '-' }}</td>
                            <td class="px-3 py-2 text-xs">{{ $movimiento['numero_operacion'] ?: ($movimiento['referencia_label'] ?: '-') }}</td>
                            <td class="px-3 py-2 text-right text-xs font-semibold {{ $movimiento['tipo'] === 'entrada' ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400' }}">
                                {{ $movimiento['tipo'] === 'entrada' ? '+' : '-' }} S/ {{ number_format((float) $movimiento['monto'], 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-3 py-6 text-center text-zinc-500">Sin movimientos en el período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
