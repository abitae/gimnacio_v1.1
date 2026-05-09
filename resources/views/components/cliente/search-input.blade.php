@props([
    'clienteSearch' => '',
    'codigoSearch' => '',
    'clientes' => null,
    'selectedCliente' => null,
    'isSearching' => false,
    'showCodigoField' => false,
])

@php
    $codigoTrim = $showCodigoField ? trim((string) $codigoSearch) : '';
@endphp

<div @class(['space-y-2' => ! $showCodigoField, 'space-y-3' => $showCodigoField])>
    @if ($showCodigoField)
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:items-end">
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Nombre, documento o correo') }}
                </label>
                <div class="relative">
                    <flux:input icon="magnifying-glass" type="search" size="xs"
                        wire:model.live.debounce.300ms="clienteSearch"
                        placeholder="{{ __('Nombre, documento o correo…') }}"
                        class="w-full" aria-label="{{ __('Buscar por nombre, documento o email') }}" />

                    @if ($isSearching && filled($clienteSearch))
                        <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400">
                            <flux:icon.loading class="size-4" />
                        </div>
                    @endif
                </div>
            </div>
            <div class="space-y-1">
                <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('Solo código interno') }}
                </label>
                <div class="relative">
                    <flux:input icon="identification" type="search" size="xs"
                        wire:model.live.debounce.300ms="codigoSearch"
                        placeholder="{{ __('Ej. 10001…') }}"
                        class="w-full" aria-label="{{ __('Buscar solo por código interno del cliente') }}" />

                    @if ($isSearching && filled($codigoTrim))
                        <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400">
                            <flux:icon.loading class="size-4" />
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="flex items-center justify-between">
            <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                {{ __('Buscar cliente') }}
            </label>
        </div>
        <div class="relative">
            <div class="relative">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="clienteSearch"
                    placeholder="{{ __('Código, nombre, documento o correo…') }}"
                    class="w-full" aria-label="{{ __('Buscar por código, nombre, documento o email') }}" />

                @if ($isSearching)
                    <div class="pointer-events-none absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400">
                        <flux:icon.loading class="size-4" />
                    </div>
                @endif
            </div>
        </div>
    @endif

    <div class="relative">
        @if (($clienteSearch || ($showCodigoField && $codigoTrim !== '')) && !$isSearching && !$selectedCliente)
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
                                    @if (filled($cliente->codigo))
                                        <span>{{ __('Cód.') }} {{ $cliente->codigo }}</span>
                                        <span class="mx-1">·</span>
                                    @endif
                                    {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                                    @if ($cliente->email)
                                        <span class="ml-2">• {{ $cliente->email }}</span>
                                    @endif
                                </div>
                            </div>
                        </flux:button>
                    @endforeach
                </div>
            @elseif ($showCodigoField ? (strlen(trim($clienteSearch)) >= 2 || strlen($codigoTrim) >= 1) : strlen(trim($clienteSearch)) >= 2)
                <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 p-4">
                    <p class="text-xs text-center text-zinc-500 dark:text-zinc-400">
                        No se encontraron clientes
                    </p>
                </div>
            @endif
        @endif
    </div>
</div>
