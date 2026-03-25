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
            <div class="w-full min-w-[12rem] sm:w-48">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="search" placeholder="{{ __('Buscar…') }}" class="w-full"
                    aria-label="{{ __('Buscar clientes') }}" />
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
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @forelse ($clientes as $cliente)
                <div wire:key="cliente-card-{{ $cliente->id }}" class="flex flex-col gap-2 rounded-lg border border-zinc-200 bg-zinc-50/50 p-2 dark:border-zinc-700 dark:bg-zinc-900/40">
                    <x-cliente.profile-card
                        :cliente="$cliente"
                        :hide-actions="true"
                        :dismissible="false"
                        :salud-link-only="true"
                    />
                    <div class="flex flex-wrap items-center justify-between gap-2 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                        <div class="flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                            @php($biotimeState = $cliente->biotime_state_bool)
                            <flux:icon name="{{ $biotimeState ? 'check-circle' : 'x-circle' }}" class="inline size-4 {{ $biotimeState ? 'text-lime-600' : 'text-red-600' }}" title="BioTime" />
                            @php($biotimeUpdate = $cliente->biotime_update_bool)
                            <flux:icon name="{{ $biotimeUpdate ? 'arrow-path' : 'x-circle' }}" class="inline size-4 {{ $biotimeUpdate ? 'text-lime-600' : 'text-red-600' }}" title="Actualización BioTime" />
                        </div>
                        <flux:button size="xs" variant="primary" icon="user-circle" type="button"
                            wire:click="verPerfil({{ $cliente->id }})">
                            {{ __('Ver perfil') }}
                        </flux:button>
                    </div>
                </div>
            @empty
                <div class="col-span-full rounded-lg border border-zinc-200 bg-white px-4 py-12 text-center text-xs text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                    {{ __('No hay clientes') }}
                </div>
            @endforelse
        </div>

        <div class="flex justify-end">
            {{ $clientes->links() }}
        </div>
    </div>
</div>
