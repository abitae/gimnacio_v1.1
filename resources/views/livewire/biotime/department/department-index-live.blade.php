<div class="space-y-6">
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="building-office-2" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Departamentos BioTime</h1>
                    <p class="text-sm text-white/90">Listar, crear, editar y eliminar departamentos (personnel)</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('biotime.index') }}" wire:navigate
                    class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white hover:bg-white/20">
                    Dashboard
                </a>
                @can('biotime.create')
                <flux:button variant="primary" class="bg-white text-purple-600 hover:bg-white/90" wire:click="openCreateModal">
                    Nuevo departamento
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
                    <flux:input type="text" wire:model.live.debounce.300ms="filterDeptCode" placeholder="Código" />
                </flux:field>
                <flux:field class="min-w-[160px]">
                    <flux:label>Buscar nombre</flux:label>
                    <flux:input type="text" wire:model.live.debounce.300ms="filterDeptName" placeholder="Nombre" />
                </flux:field>
                <flux:field class="min-w-[180px]">
                    <flux:label>Ordenar por</flux:label>
                    <select wire:model.live="ordering"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="id">ID (asc)</option>
                        <option value="-id">ID (desc)</option>
                        <option value="dept_code">Código (asc)</option>
                        <option value="-dept_code">Código (desc)</option>
                        <option value="dept_name">Nombre (asc)</option>
                        <option value="-dept_name">Nombre (desc)</option>
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
                <flux:button variant="ghost" size="sm" wire:click="loadDepartments" wire:loading.attr="disabled">
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
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">Depto. superior</th>
                        <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300">Empresa</th>
                        <th class="px-4 py-3 text-right font-medium text-zinc-700 dark:text-zinc-300">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($departments as $dept)
                        @php
                            $company = $dept['company'] ?? null;
                            $companyName = is_array($company) ? ($company['company_name'] ?? $company['company_code'] ?? '-') : '-';
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">{{ $dept['id'] ?? '-' }}</td>
                            <td class="px-4 py-3 font-mono text-zinc-900 dark:text-zinc-100">{{ $dept['dept_code'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-900 dark:text-zinc-100">{{ $dept['dept_name'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $dept['parent_dept'] ?? '-' }}</td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $companyName }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('biotime.update')
                                <flux:button size="xs" variant="ghost" wire:click="openEditModal({{ $dept['id'] ?? 0 }})">Editar</flux:button>
                                @endcan
                                @can('biotime.delete')
                                <flux:button size="xs" variant="ghost" color="red" wire:click="confirmDelete({{ $dept['id'] ?? 0 }})">Eliminar</flux:button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                No hay departamentos o no se pudo cargar la lista.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($total > 0)
            <div class="mt-4 flex justify-end border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $departmentsPaginator->links() }}
            </div>
        @endif
    </div>

    {{-- Modal crear/editar — similar a Áreas --}}
    <flux:modal name="department-form-modal" wire:model="modalOpen" focusable class="md:w-lg">
        <form wire:submit="saveDepartment">
            <div class="space-y-4 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $editingId ? 'Editar departamento' : 'Nuevo departamento' }}
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
                    <flux:label>Código de departamento <span class="text-red-500">*</span></flux:label>
                    <flux:input type="text" wire:model="formDeptCode" required placeholder="Ej: 1" />
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">El código debe ser único.</p>
                    <flux:error name="formDeptCode" />
                </flux:field>
                <flux:field>
                    <flux:label>Nombre de departamento <span class="text-red-500">*</span></flux:label>
                    <flux:input type="text" wire:model="formDeptName" required placeholder="Ej: Departamento" />
                    <flux:error name="formDeptName" />
                </flux:field>
                <flux:field>
                    <flux:label>Departamento superior</flux:label>
                    <select wire:model="formParentDept"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">------</option>
                        @foreach ($departmentsForParentSelect as $parentDept)
                            @if (($parentDept['id'] ?? 0) != $editingId)
                                <option value="{{ $parentDept['id'] ?? '' }}">{{ $parentDept['dept_name'] ?? $parentDept['dept_code'] ?? $parentDept['id'] }}</option>
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
                <flux:button type="submit" color="green" variant="primary" wire:loading.attr="disabled" wire:target="saveDepartment">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="saveDepartment" />
                        <span wire:loading.remove wire:target="saveDepartment">Confirmar</span>
                        <span wire:loading wire:target="saveDepartment">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="department-delete-modal" wire:model="showDeleteModal" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Eliminar departamento</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                ¿Estás seguro de que deseas eliminar este departamento?
            </p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" wire:click="cancelDelete" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            <flux:button color="red" variant="primary" wire:click="deleteDepartment" wire:loading.attr="disabled" wire:target="deleteDepartment">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="deleteDepartment" />
                    <span wire:loading.remove wire:target="deleteDepartment">Eliminar</span>
                    <span wire:loading wire:target="deleteDepartment">Eliminando...</span>
                </span>
            </flux:button>
        </div>
    </flux:modal>
</div>
