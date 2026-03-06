<div class="space-y-6">
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex items-center justify-between border-b border-zinc-200 p-4 dark:border-zinc-700">
            <div>
                <h1 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Empleados BioTime</h1>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Clientes por estado: activos, inactivos y suspendidos.</p>
            </div>
            <a href="{{ route('biotime.index') }}" wire:navigate
                class="rounded-lg border border-zinc-300 px-3 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                Dashboard
            </a>
        </div>

        @if ($message !== '')
            <div class="mx-4 mt-4 rounded-lg p-3 text-sm {{ $messageSuccess ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                {{ $message }}
            </div>
        @endif

        {{-- Tabs --}}
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button type="button" wire:click="switchTab('activos')"
                class="px-4 py-3 text-sm font-medium {{ $tab === 'activos' ? 'border-b-2 border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Activos
            </button>
            <button type="button" wire:click="switchTab('inactivos')"
                class="px-4 py-3 text-sm font-medium {{ $tab === 'inactivos' ? 'border-b-2 border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Inactivos
            </button>
            <button type="button" wire:click="switchTab('suspendidos')"
                class="px-4 py-3 text-sm font-medium {{ $tab === 'suspendidos' ? 'border-b-2 border-purple-600 text-purple-600 dark:border-purple-400 dark:text-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Suspendidos
            </button>
        </div>

        <div class="p-4">
            @if ($tab === 'activos')
                <div class="space-y-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Clientes activos; al sincronizar pasan a BioTime.</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:input size="sm" type="search" wire:model.live.debounce.300ms="searchActivos"
                            placeholder="Buscar por nombre o documento..." class="min-w-[200px]" />
                        <select wire:model.live="perPage" class="rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Id</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Documento</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Nombre</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">BioTime</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse ($this->clientesActivosPaginator as $c)
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-zinc-500 dark:text-zinc-400">{{ $c->id }}</td>
                                        <td class="px-3 py-2 font-mono text-zinc-900 dark:text-zinc-100">{{ $c->numero_documento }}</td>
                                        <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">{{ $c->nombres }} {{ $c->apellidos }}</td>
                                        <td class="px-3 py-2">
                                            @php($enBiotime = $c->biotime_state_bool ?? false)
                                            @if ($enBiotime)
                                                <span class="inline-flex text-lime-600 dark:text-lime-400" title="En BioTime">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @else
                                                <span class="inline-flex text-red-600 dark:text-red-400" title="No en BioTime">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">{{ ucfirst($c->estado_cliente ?? 'activo') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-zinc-500 dark:text-zinc-400">Ningún cliente activo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($this->clientesActivosPaginator->hasPages())
                        <div class="mt-4 flex justify-end border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            {{ $this->clientesActivosPaginator->links() }}
                        </div>
                    @endif
                </div>
            @elseif ($tab === 'inactivos')
                <div class="space-y-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Suspenderlos los elimina de BioTime (si existían) y los marca como suspendidos.</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:input size="sm" type="search" wire:model.live.debounce.300ms="searchInactivos"
                            placeholder="Buscar por nombre o documento..." class="min-w-[200px]" />
                        <select wire:model.live="perPage" class="rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                        @if ($this->clientesInactivosAll->isNotEmpty())
                            <flux:button size="sm" color="red" variant="ghost" wire:click="confirmSuspendMasivo"
                                wire:loading.attr="disabled" wire:target="suspendClientesMasivo">
                                Suspender todos ({{ $this->clientesInactivosAll->count() }})
                            </flux:button>
                        @endif
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Id</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Documento</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Nombre</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">BioTime</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Estado</th>
                                    <th class="px-3 py-2 text-right font-medium text-zinc-600 dark:text-zinc-400">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse ($this->clientesInactivosPaginator as $c)
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-zinc-500 dark:text-zinc-400">{{ $c->id }}</td>
                                        <td class="px-3 py-2 font-mono text-zinc-900 dark:text-zinc-100">{{ $c->numero_documento }}</td>
                                        <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">{{ $c->nombres }} {{ $c->apellidos }}</td>
                                        <td class="px-3 py-2">
                                            @php($enBiotime = $c->biotime_state_bool ?? false)
                                            @if ($enBiotime)
                                                <span class="inline-flex text-lime-600 dark:text-lime-400" title="En BioTime">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @else
                                                <span class="inline-flex text-red-600 dark:text-red-400" title="No en BioTime">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400">{{ ucfirst($c->estado_cliente ?? 'inactivo') }}</span>
                                        </td>
                                        <td class="px-3 py-2 text-right">
                                            <flux:button size="xs" variant="ghost" color="red" wire:click="confirmSuspend({{ $c->id }})">
                                                Suspender
                                            </flux:button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-3 py-6 text-center text-zinc-500 dark:text-zinc-400">Ningún cliente inactivo.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($this->clientesInactivosPaginator->hasPages())
                        <div class="mt-4 flex justify-end border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            {{ $this->clientesInactivosPaginator->links() }}
                        </div>
                    @endif
                </div>
            @else
                <div class="space-y-3">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Eliminados de BioTime; siguen en la app. Al matricularlos de nuevo pasan a activos.</p>
                    <div class="flex flex-wrap items-center gap-3">
                        <flux:input size="sm" type="search" wire:model.live.debounce.300ms="searchSuspendidos"
                            placeholder="Buscar por nombre o documento..." class="min-w-[200px]" />
                        <select wire:model.live="perPage" class="rounded-lg border border-zinc-300 px-2 py-1 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="10">10</option>
                            <option value="15">15</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Id</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Documento</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Nombre</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">BioTime</th>
                                    <th class="px-3 py-2 text-left font-medium text-zinc-600 dark:text-zinc-400">Estado</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse ($this->clientesSuspendidosPaginator as $c)
                                    <tr>
                                        <td class="px-3 py-2 font-mono text-zinc-500 dark:text-zinc-400">{{ $c->id }}</td>
                                        <td class="px-3 py-2 font-mono text-zinc-900 dark:text-zinc-100">{{ $c->numero_documento }}</td>
                                        <td class="px-3 py-2 text-zinc-900 dark:text-zinc-100">{{ $c->nombres }} {{ $c->apellidos }}</td>
                                        <td class="px-3 py-2">
                                            @php($enBiotime = $c->biotime_state_bool ?? false)
                                            @if ($enBiotime)
                                                <span class="inline-flex text-lime-600 dark:text-lime-400" title="En BioTime">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @else
                                                <span class="inline-flex text-red-600 dark:text-red-400" title="No en BioTime">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2">
                                            <span class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">{{ ucfirst($c->estado_cliente ?? 'suspendido') }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-3 py-6 text-center text-zinc-500 dark:text-zinc-400">Ningún cliente suspendido.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($this->clientesSuspendidosPaginator->hasPages())
                        <div class="mt-4 flex justify-end border-t border-zinc-200 pt-3 dark:border-zinc-700">
                            {{ $this->clientesSuspendidosPaginator->links() }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

    {{-- Modal: confirmar suspender uno --}}
    <flux:modal name="suspend-one-modal" wire:model="confirmSuspendId" focusable class="md:w-md">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Suspender cliente</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Se eliminará de BioTime (si existe) y pasará a estado suspendido en la app.
            </p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="cancelSuspend" type="button">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button color="red" variant="primary" wire:click="suspendCliente({{ $confirmSuspendId ?? 0 }})" wire:loading.attr="disabled"
                wire:target="suspendCliente">
                <span wire:loading.remove wire:target="suspendCliente">Suspender</span>
                <span wire:loading wire:target="suspendCliente">...</span>
            </flux:button>
        </div>
    </flux:modal>

    {{-- Modal: confirmar suspender todos --}}
    <flux:modal name="suspend-masivo-modal" wire:model="showSuspendMasivoModal" focusable class="md:w-md">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Suspender todos los inactivos</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Se eliminarán de BioTime (si existen) y pasarán a estado suspendido. Total: {{ $this->clientesInactivosAll->count() }}.
            </p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="cancelSuspend" type="button">Cancelar</flux:button>
            </flux:modal.close>
            <flux:button color="red" variant="primary" wire:click="suspendClientesMasivo" wire:loading.attr="disabled"
                wire:target="suspendClientesMasivo">
                <span wire:loading.remove wire:target="suspendClientesMasivo">Suspender todos</span>
                <span wire:loading wire:target="suspendClientesMasivo">Procesando...</span>
            </flux:button>
        </div>
    </flux:modal>
</div>
