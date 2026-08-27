<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Pagar cuota</h1>
    @php
        $mat = $installment->clienteMatricula;
        $cliente = $installment->plan->cliente ?? $mat?->cliente;
    @endphp
    <p class="text-sm text-zinc-500">Cuota {{ $installment->numero_cuota }} — {{ $cliente?->nombres }} {{ $cliente?->apellidos }} — Vence: {{ $installment->fecha_vencimiento->format('d/m/Y') }}</p>
    <p class="text-sm text-zinc-500">{{ __('Saldo pendiente: S/ :saldo', ['saldo' => number_format($saldoPendiente, 2)]) }}</p>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>{{ __('Descuento (S/)') }}</flux:label>
            <flux:input type="number" step="0.01" min="0" wire:model.live="form.descuento_monto" />
        </flux:field>
        <flux:field>
            <flux:label>{{ __('Monto a cobrar (S/)') }}</flux:label>
            <flux:input type="number" step="0.01" wire:model="form.monto" required />
        </flux:field>
        <flux:field>
            <flux:label>Fecha de pago</flux:label>
            <flux:input type="date" wire:model="form.fecha_pago" required />
        </flux:field>
        @if ((float) ($form['monto'] ?? 0) > 0)
        @include('livewire.shared.pago-mixto-fields', [
            'formPrefix' => 'form',
            'totalAsignado' => $this->totalAsignado,
            'diferencia' => $this->diferencia,
            'agregarAction' => 'agregarFormaPago',
            'quitarAction' => 'quitarFormaPago',
        ])
        @endif
        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('clientes.cuotas', ['cliente' => $installment->plan->cliente_id, 'matricula' => $installment->cliente_matricula_id]) }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Registrar pago</flux:button>
        </div>
    </form>
</div>
