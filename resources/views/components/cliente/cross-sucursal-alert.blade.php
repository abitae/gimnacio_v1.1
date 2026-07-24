@props(['matches' => collect()])

@if ($matches->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950 dark:border-amber-600/40 dark:bg-amber-950/30 dark:text-amber-100']) }} role="status">
        <div class="font-semibold">Cliente registrado en otra sucursal</div>
        <ul class="mt-2 space-y-1.5">
            @foreach ($matches as $match)
                <li>
                    <span class="font-medium">{{ $match['sucursal_nombre'] }}</span>
                    <span class="text-amber-800 dark:text-amber-200">({{ $match['estado_cliente'] }})</span>
                    @if ($match['tiene_deuda'])
                        — Deuda pendiente: <span class="font-semibold">S/ {{ number_format($match['saldo_deuda'], 2) }}</span>
                    @else
                        — Sin deuda pendiente
                    @endif
                </li>
            @endforeach
        </ul>
    </div>
@endif
