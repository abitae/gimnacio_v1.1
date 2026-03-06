<div class="space-y-4">
    <div class="flex items-center justify-between">
        <a href="{{ route('clientes.index') }}" wire:navigate class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center gap-1">
            <flux:icon name="arrow-left" class="w-4 h-4" /> Volver a clientes
        </a>
    </div>
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
        <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Etiquetas CRM</h1>
        <p class="text-sm text-zinc-500">{{ $cliente->nombres }} {{ $cliente->apellidos }}</p>
    </div>
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 bg-white dark:bg-zinc-800">
        <livewire:crm.tag-picker-live entity-type="cliente" :cliente-id="$clienteId" :key="'cliente-tags-'.$clienteId" />
    </div>
</div>
