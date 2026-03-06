@props(['roleId', 'formData', 'permissions'])

@php
    $roleLabel = fn ($name) => \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
@endphp

<flux:modal name="rol-form" class="space-y-4" wire:model="modalState.form" focusable>
    <form wire:submit="save" class="space-y-4">
        <flux:heading size="lg">{{ $roleId ? 'Editar rol' : 'Nuevo rol' }}</flux:heading>
        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input wire:model="formData.name" placeholder="ej: editor" />
            <flux:error name="formData.name" />
        </flux:field>
        <flux:field>
            <flux:label>Guard</flux:label>
            <select wire:model="formData.guard_name"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="web">web</option>
                <option value="api">api</option>
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Permisos</flux:label>
            <div class="max-h-48 overflow-y-auto space-y-2 rounded border border-zinc-200 dark:border-zinc-600 p-2">
                @foreach ($permissions as $perm)
                    <label class="flex items-center gap-2">
                        <flux:checkbox wire:model="formData.permissions" value="{{ $perm->name }}" />
                        <span class="text-sm">{{ $roleLabel($perm->name) }}</span>
                    </label>
                @endforeach
                @if ($permissions->isEmpty())
                    <p class="text-xs text-zinc-500">No hay permisos creados. Crea permisos desde el seeder o desde código.</p>
                @endif
            </div>
        </flux:field>
        <div class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" wire:click="closeModal">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">Guardar</flux:button>
        </div>
    </form>
</flux:modal>
