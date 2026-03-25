@props([
    'clienteSearch' => '',
    'clientes' => null,
    'selectedCliente' => null,
    'isSearching' => false,
])

<div class="space-y-2">
    <div class="flex items-center justify-between">
        <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
            Buscar Cliente
        </label>
    </div>
    <div class="relative">
        <div class="relative">
            <flux:input icon="magnifying-glass" type="search" size="xs"
                wire:model.live.debounce.300ms="clienteSearch" 
                placeholder="Buscar: código, nombre..."
                class="w-full" aria-label="Buscar cliente" />
            
            @if ($isSearching)
                <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400">
                    <flux:icon.loading class="size-4" />
                </div>
            @endif
        </div>
        
        @if ($clienteSearch && !$isSearching && !$selectedCliente)
            @if ($clientes && $clientes->count() > 0)
                <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 max-h-60 overflow-y-auto">
                    @foreach ($clientes as $cliente)
                        <flux:button type="button" wire:click="selectCliente({{ $cliente->id }})" variant="ghost" size="xs"
                            class="!h-auto min-h-0 w-full !justify-start rounded-none border-0 px-4 py-2 text-left shadow-none hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            <div class="w-full text-left">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                </div>
                                <div class="text-zinc-500 dark:text-zinc-400">
                                    {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                                    @if ($cliente->email)
                                        <span class="ml-2">• {{ $cliente->email }}</span>
                                    @endif
                                </div>
                            </div>
                        </flux:button>
                    @endforeach
                </div>
            @elseif (strlen(trim($clienteSearch)) >= 2)
                <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 p-4">
                    <p class="text-xs text-center text-zinc-500 dark:text-zinc-400">
                        No se encontraron clientes
                    </p>
                </div>
            @endif
        @endif
    </div>
</div>
