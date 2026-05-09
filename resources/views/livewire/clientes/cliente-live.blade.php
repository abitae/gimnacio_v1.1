<div class="space-y-3 rounded-lg border border-zinc-200 p-3">
    <div class="flex flex-col gap-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Listado de clientes') }}</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ __('Búsqueda y acceso rápido. La ficha y las acciones están en') }}
                    <a href="{{ route('clientes.perfil.index') }}" wire:navigate class="text-violet-600 hover:underline dark:text-violet-400">{{ __('Perfil de cliente') }}</a>.
                </p>
            </div>
            <a href="{{ route('clientes.perfil.index') }}" wire:navigate>
                <flux:button icon="user-circle" color="purple" variant="primary" size="sm" type="button">
                    {{ __('Abrir perfil') }}
                </flux:button>
            </a>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <div class="w-full min-w-[12rem] sm:w-44">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Nombre, apellidos, documento o correo…') }}"
                    class="w-full"
                    aria-label="{{ __('Buscar clientes por nombre, documento o email') }}" />
            </div>
            <div class="w-full min-w-[10rem] sm:w-36">
                <flux:input icon="identification" type="search" size="xs"
                    wire:model.live.debounce.300ms="codigoSearch"
                    placeholder="{{ __('Solo código…') }}"
                    class="w-full"
                    aria-label="{{ __('Filtrar por código interno del cliente') }}" />
            </div>
            <div class="w-32">
                <select wire:model.live="estadoFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Estado') }}">
                    <option value="">{{ __('Todos') }}</option>
                    <option value="activo">{{ __('Activo') }}</option>
                    <option value="inactivo">{{ __('Inactivo') }}</option>
                    <option value="suspendido">{{ __('Suspendido') }}</option>
                </select>
            </div>
            <div class="w-28">
                <select wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Por página') }}">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700"
            wire:loading.delay.class="opacity-60 pointer-events-none"
            wire:target="search,codigoSearch,estadoFilter,perPage">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Cliente') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Código') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Documento') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Contacto') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Estado') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Deuda') }}</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400" title="BioTime">BT</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse ($clientes as $cliente)
                        @php
                            $estadoClienteBadge = match ($cliente->estado_cliente) {
                                'activo' => 'green',
                                'inactivo' => 'zinc',
                                default => 'red',
                            };
                            $deudaTotal = (float) $cliente->deuda_total;
                        @endphp
                        <tr wire:key="cliente-row-{{ $cliente->id }}">
                            <td class="px-3 py-2">
                                <div class="flex min-w-0 max-w-[14rem] items-center gap-2">
                                    @if ($cliente->foto)
                                        <img src="{{ asset('storage/' . $cliente->foto) }}" alt=""
                                            class="size-9 shrink-0 rounded-full border border-zinc-200 object-cover dark:border-zinc-600">
                                    @else
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                            {{ strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 tabular-nums font-semibold text-violet-700 dark:text-violet-300">
                                {{ filled($cliente->codigo) ? $cliente->codigo : '—' }}
                            </td>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-300">
                                <span class="whitespace-nowrap">{{ $cliente->tipo_documento }}</span>
                                <span class="tabular-nums">{{ $cliente->numero_documento }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="max-w-[12rem] space-y-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                    @if (filled($cliente->email))
                                        <p class="truncate" title="{{ $cliente->email }}">{{ $cliente->email }}</p>
                                    @endif
                                    @if (filled($cliente->telefono))
                                        <p class="tabular-nums text-zinc-500 dark:text-zinc-500">{{ $cliente->telefono }}</p>
                                    @endif
                                    @if (! filled($cliente->email) && ! filled($cliente->telefono))
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <flux:badge :color="$estadoClienteBadge">{{ ucfirst($cliente->estado_cliente) }}</flux:badge>
                            </td>
                            <td class="px-3 py-2">
                                @if ($deudaTotal > 0)
                                    <flux:badge color="red" class="tabular-nums text-xs">S/ {{ number_format($deudaTotal, 2) }}</flux:badge>
                                @else
                                    <flux:badge color="green" class="text-xs">{{ __('Sin deuda') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center justify-center gap-0.5">
                                    @php($biotimeState = $cliente->biotime_state_bool)
                                    <flux:icon name="{{ $biotimeState ? 'check-circle' : 'x-circle' }}" class="size-4 {{ $biotimeState ? 'text-lime-600' : 'text-red-600' }}" title="BioTime" />
                                    @php($biotimeUpdate = $cliente->biotime_update_bool)
                                    <flux:icon name="{{ $biotimeUpdate ? 'arrow-path' : 'x-circle' }}" class="size-4 {{ $biotimeUpdate ? 'text-lime-600' : 'text-red-600' }}" title="{{ __('Actualización BioTime') }}" />
                                </div>
                            </td>
                            <td class="px-3 py-2 text-right">
                                <flux:button size="xs" variant="primary" icon="user-circle" type="button"
                                    wire:click="verPerfil({{ $cliente->id }})">
                                    {{ __('Ver perfil') }}
                                </flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('No hay clientes') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            {{ $clientes->links() }}
        </div>
    </div>
</div>
