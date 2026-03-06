<div class="space-y-6">
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="map-pin" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Áreas BioTime</h1>
                    <p class="text-sm text-white/90">Listar, crear, editar y eliminar áreas (personnel)</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('biotime.index') }}" wire:navigate
                    class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white hover:bg-white/20">
                    Dashboard
                </a>
                @can('biotime.create')
                <flux:button variant="primary" class="bg-white text-purple-600 hover:bg-white/90" wire:click="openCreateModal">
                    Nueva área
                </flux:button>
                @endcan
            </div>
        </div>
    </div>

    @if ($message !== '')
        <div class="rounded-lg p-4 {{ $messageSuccess ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
            {{ $message }}
        </div>
    @endif

    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <div class="flex flex-wrap items-end gap-4">
                <flux:field class="min-w-[160px]">
                    <flux:label>Buscar código</flux:label>
                    <flux:input type="text" wire:model.live.debounce.300ms="filterAreaCode" placeholder="Código de área" />
                </flux:field>
                <flux:field class="min-w-[160px]">
                    <flux:label>Buscar nombre</flux:label>
                    <flux:input type="text" wire:model.live.debounce.300ms="filterAreaName" placeholder="Nombre de área" />
                </flux:field>
                <flux:field class="min-w-[180px]">
                    <flux:label>Ordenar por</flux:label>
                    <select wire:model.live="ordering"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="id">ID (asc)</option>
                        <option value="-id">ID (desc)</option>
                        <option value="area_code">Código (asc)</option>
                        <option value="-area_code">Código (desc)</option>
                        <option value="area_name">Nombre (asc)</option>
                        <option value="-area_name">Nombre (desc)</option>
                    </select>
                </flux:field>
                <flux:field class="min-w-[120px]">
                    <flux:label>Por página</flux:label>
                    <select wire:model.live="pageSize"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="10">10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </flux:field>
                <flux:button variant="ghost" size="sm" wire:click="loadAreas" wire:loading.attr="disabled">
                    Actualizar
                </flux:button>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">ID</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">Código</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">Nombre</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">Área padre</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">Empresa</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($areas as $area)
                        @php
                            $company = $area['company'] ?? null;
                            $companyName = is_array($company) ? ($company['company_name'] ?? $company['company_code'] ?? '-') : '-';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">{{ $area['id'] ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-zinc-900 dark:text-zinc-100">{{ $area['area_code'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $area['area_name'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $area['parent_area'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $companyName }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('biotime.update')
                                <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $area['id'] ?? 0 }})">Editar</flux:button>
                                @endcan
                                @can('biotime.delete')
                                <flux:button size="xs" variant="ghost" color="red" wire:click="confirmDelete({{ $area['id'] ?? 0 }})">Eliminar</flux:button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No hay áreas o no se pudo cargar la lista.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($total > 0)
            <div class="mt-4 flex justify-end border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $areasPaginator->links() }}
            </div>
        @endif
    </div>

    {{-- Modal crear/editar — similar a la referencia --}}
    <flux:modal name="area-form-modal" wire:model="modalOpen" focusable class="md:w-lg">
        <form wire:submit="saveArea">
            <div class="space-y-4 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $editingId ? 'Editar área' : 'Nueva área' }}
                </h2>
                <flux:field>
                    <flux:label>Empresa <span class="text-red-500">*</span></flux:label>
                    <select wire:model="formCompany" required
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Seleccionar empresa...</option>
                        @foreach ($companiesList as $company)
                            <option value="{{ $company['id'] ?? '' }}">{{ $company['company_name'] ?? $company['company_code'] ?? $company['id'] }}</option>
                        @endforeach
                    </select>
                    <flux:error name="formCompany" />
                </flux:field>
                <flux:field>
                    <flux:label>Código de área <span class="text-red-500">*</span></flux:label>
                    <flux:input type="text" wire:model="formAreaCode" required placeholder="Ej: 3" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">El código debe ser único.</p>
                    <flux:error name="formAreaCode" />
                </flux:field>
                <flux:field>
                    <flux:label>Nombre de área <span class="text-red-500">*</span></flux:label>
                    <flux:input type="text" wire:model="formAreaName" required placeholder="Ej: prueba2" />
                    <flux:error name="formAreaName" />
                </flux:field>
                <flux:field>
                    <flux:label>Área superior</flux:label>
                    <select wire:model="formParentArea"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">------</option>
                        @foreach ($areasForParentSelect as $parentArea)
                            @if (($parentArea['id'] ?? 0) != $editingId)
                                <option value="{{ $parentArea['id'] ?? '' }}">{{ $parentArea['area_name'] ?? $parentArea['area_code'] ?? $parentArea['id'] }}</option>
                            @endif
                        @endforeach
                    </select>
                </flux:field>
            </div>
            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button type="button" variant="ghost" wire:click="closeModal">
                        Cancelar
                    </flux:button>
                </flux:modal.close>
                <flux:button type="submit" color="green" variant="primary" wire:loading.attr="disabled" wire:target="saveArea">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="saveArea" />
                        <span wire:loading.remove wire:target="saveArea">Confirmar</span>
                        <span wire:loading wire:target="saveArea">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal confirmar eliminar (Flux UI) --}}
    <flux:modal name="area-delete-modal" wire:model="showDeleteModal" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Eliminar área</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                ¿Estás seguro de que deseas eliminar esta área?
            </p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="cancelDelete" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            <flux:button color="red" variant="primary" wire:click="deleteArea" wire:loading.attr="disabled" wire:target="deleteArea">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="deleteArea" />
                    <span wire:loading.remove wire:target="deleteArea">Eliminar</span>
                    <span wire:loading wire:target="deleteArea">Eliminando...</span>
                </span>
            </flux:button>
        </div>
    </flux:modal>
</div>
