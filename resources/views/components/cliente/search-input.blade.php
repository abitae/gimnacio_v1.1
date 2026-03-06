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
                <div class="absolute right-2 top-1/2 -translate-y-1/2">
                    <svg class="animate-spin h-4 w-4 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
            @endif
        </div>
        
        @if ($clienteSearch && !$isSearching && !$selectedCliente)
            @if ($clientes && $clientes->count() > 0)
                <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 max-h-60 overflow-y-auto">
                    @foreach ($clientes as $cliente)
                        <button type="button"
                            wire:click="selectCliente({{ $cliente->id }})"
                            class="w-full px-4 py-2 text-left text-xs hover:bg-zinc-50 dark:hover:bg-zinc-700 focus:bg-zinc-50 dark:focus:bg-zinc-700 focus:outline-none transition-colors">
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $cliente->nombres }} {{ $cliente->apellidos }}
                            </div>
                            <div class="text-zinc-500 dark:text-zinc-400">
                                {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                                @if ($cliente->email)
                                    <span class="ml-2">• {{ $cliente->email }}</span>
                                @endif
                            </div>
                        </button>
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
