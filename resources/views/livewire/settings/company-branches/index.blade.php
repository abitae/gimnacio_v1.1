<div class="space-y-4 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
    <div>
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Super administración</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Configura la empresa, administra sucursales y crea administradores por sucursal.</p>
    </div>

    @if (session('success'))
        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800 dark:border-green-900/40 dark:bg-green-950/30 dark:text-green-300">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900/40 dark:bg-red-950/30 dark:text-red-300">
            {{ session('error') }}
        </div>
    @endif

    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Datos de la empresa</h2>
            <p class="text-sm text-zinc-600 dark:text-zinc-400">Estos datos identifican a la empresa propietaria del sistema.</p>
        </div>

        <form wire:submit="saveEmpresa" class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1 block text-sm font-medium">Nombre comercial</label>
                <input wire:model="empresaForm.nombre" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                @error('empresaForm.nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Razón social</label>
                <input wire:model="empresaForm.razon_social" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">RUC</label>
                <input wire:model="empresaForm.ruc" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Email corporativo</label>
                <input wire:model="empresaForm.email" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Teléfono</label>
                <input wire:model="empresaForm.telefono" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Estado</label>
                <select wire:model="empresaForm.estado" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                    <option value="activa">Activa</option>
                    <option value="inactiva">Inactiva</option>
                </select>
            </div>
            <div class="md:col-span-2">
                <label class="mb-1 block text-sm font-medium">Dirección fiscal</label>
                <textarea wire:model="empresaForm.direccion" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"></textarea>
            </div>
            <div class="md:col-span-2 flex justify-end">
                <flux:button type="submit" variant="primary">Guardar empresa</flux:button>
            </div>
        </form>
    </section>

    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Sucursales</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Cada sucursal tiene su propia operación, usuarios y datos visibles.</p>
            </div>
            <flux:button wire:click="openCreateSucursal" variant="primary" icon="plus">Nueva sucursal</flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">Sucursal</th>
                        <th class="px-4 py-2 text-left">Contacto</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Principal</th>
                        <th class="px-4 py-2 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($sucursales as $sucursal)
                        <tr>
                            <td class="px-4 py-2">{{ $sucursal->codigo }}</td>
                            <td class="px-4 py-2">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $sucursal->nombre }}</div>
                                <div class="text-xs text-zinc-500">{{ $sucursal->direccion ?: 'Sin dirección' }}</div>
                            </td>
                            <td class="px-4 py-2">
                                <div>{{ $sucursal->telefono ?: 'Sin teléfono' }}</div>
                                <div class="text-xs text-zinc-500">{{ $sucursal->email ?: 'Sin email' }}</div>
                            </td>
                            <td class="px-4 py-2">{{ $sucursal->estado }}</td>
                            <td class="px-4 py-2">{{ $sucursal->es_principal ? 'Sí' : 'No' }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <flux:button size="xs" variant="ghost" wire:click="openEditSucursal({{ $sucursal->id }})">Editar</flux:button>
                                    <flux:button
                                        size="xs"
                                        variant="ghost"
                                        color="red"
                                        wire:click="deleteSucursal({{ $sucursal->id }})"
                                        wire:confirm="Esta accion eliminara la sucursal. Continuar?"
                                    >
                                        Eliminar
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-zinc-500">No hay sucursales registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="space-y-4 rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900/40">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Administradores de sucursal</h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400">Cada administrador de sucursal queda asignado a una única sede con el rol <code>administrador_sucursal</code>.</p>
            </div>
            <flux:button wire:click="openCreateAdmin" variant="primary" icon="plus">Nuevo administrador</flux:button>
        </div>

        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-4 py-2 text-left">Nombre</th>
                        <th class="px-4 py-2 text-left">Email</th>
                        <th class="px-4 py-2 text-left">Sucursal</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-left">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($branchAdmins as $admin)
                        <tr>
                            <td class="px-4 py-2 font-medium text-zinc-900 dark:text-zinc-100">{{ $admin->name }}</td>
                            <td class="px-4 py-2">{{ $admin->email }}</td>
                            <td class="px-4 py-2">{{ $admin->sucursales->pluck('nombre')->join(', ') ?: 'Sin sucursal' }}</td>
                            <td class="px-4 py-2">{{ $admin->estado ?? 'activo' }}</td>
                            <td class="px-4 py-2">
                                <div class="flex gap-2">
                                    <flux:button size="xs" variant="ghost" wire:click="openEditAdmin({{ $admin->id }})">Editar</flux:button>
                                    @if ($admin->id !== auth()->id())
                                        <flux:button size="xs" variant="ghost" color="red" wire:click="deleteAdmin({{ $admin->id }})" wire:confirm="¿Eliminar este administrador de sucursal?">Eliminar</flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-zinc-500">No hay administradores de sucursal creados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <flux:modal wire:model="showSucursalModal" class="md:w-lg">
        <form wire:submit="saveSucursal" class="space-y-3 p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $sucursalId ? 'Editar sucursal' : 'Nueva sucursal' }}</h2>
            <div>
                <label class="mb-1 block text-sm font-medium">Código</label>
                <input wire:model="sucursalForm.codigo" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                @error('sucursalForm.codigo') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Nombre</label>
                <input wire:model="sucursalForm.nombre" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                @error('sucursalForm.nombre') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Dirección</label>
                <textarea wire:model="sucursalForm.direccion" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800"></textarea>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Teléfono</label>
                    <input wire:model="sucursalForm.telefono" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Email</label>
                    <input wire:model="sucursalForm.email" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                </div>
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Estado</label>
                    <select wire:model="sucursalForm.estado" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="activa">Activa</option>
                        <option value="inactiva">Inactiva</option>
                    </select>
                </div>
                <label class="mt-7 flex items-center gap-2 text-sm">
                    <input type="checkbox" wire:model="sucursalForm.es_principal" class="rounded border-zinc-300">
                    Marcar como principal
                </label>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showSucursalModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar sucursal</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal wire:model="showAdminModal" class="md:w-lg">
        <form wire:submit="saveAdmin" class="space-y-3 p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ $adminUserId ? 'Editar administrador de sucursal' : 'Nuevo administrador de sucursal' }}</h2>
            <div>
                <label class="mb-1 block text-sm font-medium">Nombre</label>
                <input wire:model="adminForm.name" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                @error('adminForm.name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Email</label>
                <input wire:model="adminForm.email" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                @error('adminForm.email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Contraseña {{ $adminUserId ? '(dejar en blanco para no cambiar)' : '' }}</label>
                <input type="password" wire:model="adminForm.password" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                @error('adminForm.password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-medium">Confirmar contraseña</label>
                <input type="password" wire:model="adminForm.password_confirmation" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
            </div>
            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label class="mb-1 block text-sm font-medium">Estado</label>
                    <select wire:model="adminForm.estado" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="activo">Activo</option>
                        <option value="inactivo">Inactivo</option>
                    </select>
                    @error('adminForm.estado') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium">Sucursal asignada</label>
                    <select wire:model="adminForm.sucursal_id" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800">
                        <option value="">Seleccionar sucursal</option>
                        @foreach ($sucursales as $sucursal)
                            <option value="{{ $sucursal->id }}">{{ $sucursal->nombre }}</option>
                        @endforeach
                    </select>
                    @error('adminForm.sucursal_id') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <flux:button type="button" variant="ghost" wire:click="$set('showAdminModal', false)">Cancelar</flux:button>
                <flux:button type="submit" variant="primary">Guardar administrador</flux:button>
            </div>
        </form>
    </flux:modal>
</div>
