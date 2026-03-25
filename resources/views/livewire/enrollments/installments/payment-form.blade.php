<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Pagar cuota</h1>
    @php
        $mat = $installment->clienteMatricula;
        $cliente = $installment->plan->cliente ?? $mat?->cliente;
    @endphp
    <p class="text-sm text-zinc-500">Cuota {{ $installment->numero_cuota }} — {{ $cliente?->nombres }} {{ $cliente?->apellidos }} — Vence: {{ $installment->fecha_vencimiento->format('d/m/Y') }}</p>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Monto (S/)</flux:label>
            <flux:input type="number" step="0.01" wire:model="form.monto" required />
        </flux:field>
        <flux:field>
            <flux:label>Fecha de pago</flux:label>
            <flux:input type="date" wire:model="form.fecha_pago" required />
        </flux:field>
        <flux:field>
            <flux:label>Método de pago</flux:label>
            <select wire:model="form.payment_method_id" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="">Seleccionar</option>
                @foreach($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->nombre }}</option>
                @endforeach
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Número de operación</flux:label>
            <flux:input wire:model="form.numero_operacion" />
        </flux:field>
        <flux:field>
            <flux:label>Entidad financiera</flux:label>
            <flux:input wire:model="form.entidad_financiera" />
        </flux:field>
        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('clientes.cuotas', ['cliente' => $installment->plan->cliente_id, 'matricula' => $installment->cliente_matricula_id]) }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Registrar pago</flux:button>
        </div>
    </form>
</div>
