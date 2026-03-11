<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cupón: {{ $coupon->codigo }}</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">{{ $coupon->nombre }}</p>
        </div>
        <flux:button variant="ghost" size="xs" href="{{ route('cupones.index') }}" wire:navigate>Volver</flux:button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500 dark:text-zinc-400">Descuento</span>
            <p class="font-medium">S/ {{ number_format($coupon->valor_descuento, 2) }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500 dark:text-zinc-400">Vigencia</span>
            <p class="font-medium">{{ $coupon->fecha_inicio->format('d/m/Y') }} - {{ $coupon->fecha_vencimiento->format('d/m/Y') }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500 dark:text-zinc-400">Usos</span>
            <p class="font-medium">{{ $coupon->cantidad_usada }}{{ $coupon->cantidad_max_usos ? ' / ' . $coupon->cantidad_max_usos : ' (ilimitado)' }}</p>
        </div>
        <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3">
            <span class="text-zinc-500 dark:text-zinc-400">Aplica a</span>
            <p class="font-medium">{{ ucfirst($coupon->aplica_a) }}</p>
        </div>
    </div>
    <h2 class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Historial de usos</h2>
    <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-xs">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium">Fecha</th>
                    <th class="px-4 py-2 text-left font-medium">Descuento aplicado</th>
                    <th class="px-4 py-2 text-left font-medium">Usado por</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($usages as $u)
                    <tr>
                        <td class="px-4 py-2">{{ $u->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">S/ {{ number_format($u->monto_descuento_aplicado, 2) }}</td>
                        <td class="px-4 py-2">{{ $u->usadoPor?->name ?? '-' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-4 py-6 text-center text-zinc-500">Aún no se ha usado este cupón</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="flex justify-end">{{ $usages->links() }}</div>
</div>
