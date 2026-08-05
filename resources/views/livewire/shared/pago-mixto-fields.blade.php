@php
    $lineasPago = data_get($this, $formPrefix.'.pagos', []);
    $diferenciaPago = round((float) $diferencia, 2);
    $permiteSegunda = $permiteSegunda ?? true;
@endphp

<div class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ __('Formas de pago') }}</label>
        @if ($permiteSegunda && count($lineasPago) < 2)
            <flux:button type="button" variant="ghost" size="xs" wire:click="{{ $agregarAction }}">
                {{ __('Agregar segunda forma') }}
            </flux:button>
        @endif
    </div>

    @foreach ($lineasPago as $index => $linea)
        <div wire:key="{{ $formPrefix }}-pago-{{ $index }}" class="space-y-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-zinc-600 dark:text-zinc-400">{{ __('Forma :numero', ['numero' => $index + 1]) }}</span>
                @if ($index > 0)
                    <flux:button type="button" variant="ghost" size="xs" color="red" wire:click="{{ $quitarAction }}({{ $index }})">
                        {{ __('Quitar') }}
                    </flux:button>
                @endif
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Método') }}</label>
                    <select wire:model.live="{{ $formPrefix }}.pagos.{{ $index }}.payment_method_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800" required>
                        <option value="">{{ __('Selecciona…') }}</option>
                        @foreach ($paymentMethods as $pm)
                            <option value="{{ $pm->id }}">{{ $pm->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <flux:input size="xs" type="number" min="0.01" step="0.01"
                    wire:model.live.number="{{ $formPrefix }}.pagos.{{ $index }}.monto"
                    label="{{ __('Importe (S/)') }}" required />
            </div>
            <div class="grid gap-2 sm:grid-cols-2">
                <flux:input size="xs" wire:model="{{ $formPrefix }}.pagos.{{ $index }}.numero_operacion" label="{{ __('Nº operación') }}" />
                <flux:input size="xs" wire:model="{{ $formPrefix }}.pagos.{{ $index }}.entidad_financiera" label="{{ __('Entidad') }}" />
            </div>
        </div>
    @endforeach

    <div @class([
        'rounded-lg border px-3 py-2 text-xs',
        'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300' => abs($diferenciaPago) < 0.01,
        'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300' => $diferenciaPago > 0.009,
        'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300' => $diferenciaPago < -0.009,
    ])>
        {{ __('Asignado: S/ :asignado', ['asignado' => number_format((float) $totalAsignado, 2)]) }} ·
        @if (abs($diferenciaPago) < 0.01)
            {{ __('Distribución completa') }}
        @elseif ($diferenciaPago > 0)
            {{ __('Falta: S/ :monto', ['monto' => number_format($diferenciaPago, 2)]) }}
        @else
            {{ __('Excede: S/ :monto', ['monto' => number_format(abs($diferenciaPago), 2)]) }}
        @endif
    </div>
</div>
