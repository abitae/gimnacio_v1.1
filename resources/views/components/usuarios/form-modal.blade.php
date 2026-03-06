@props(['userId', 'formData', 'roles'])

@php
    $roleLabel = fn ($name) => \Illuminate\Support\Str::title(str_replace('_', ' ', $name));
@endphp

<flux:modal name="usuario-form" class="space-y-4" wire:model="modalState.form">
    <form wire:submit="save" class="space-y-4">
        <flux:heading size="lg">{{ $userId ? 'Editar usuario' : 'Nuevo usuario' }}</flux:heading>
        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input wire:model="formData.name" placeholder="Nombre completo" />
            <flux:error name="formData.name" />
        </flux:field>
        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input type="email" wire:model="formData.email" placeholder="email@ejemplo.com" />
            <flux:error name="formData.email" />
        </flux:field>
        <flux:field>
            <flux:label>Contraseña {{ $userId ? '(dejar en blanco para no cambiar)' : '' }}</flux:label>
            <flux:input type="password" wire:model="formData.password" placeholder="••••••••" />
            <flux:error name="formData.password" />
        </flux:field>
        @if (!$userId)
            <flux:field>
                <flux:label>Confirmar contraseña</flux:label>
                <flux:input type="password" wire:model="formData.password_confirmation" placeholder="••••••••" />
            </flux:field>
        @endif
        <flux:field>
            <flux:label>Estado</flux:label>
            <select wire:model="formData.estado"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Roles</flux:label>
            <div class="space-y-2">
                @foreach ($roles as $role)
                    <label class="flex items-center gap-2">
                        <flux:checkbox wire:model="formData.roles" value="{{ $role->name }}" />
                        <span class="text-sm">{{ $roleLabel($role->name) }}</span>
                    </label>
                @endforeach
            </div>
        </flux:field>
        <div class="flex justify-end gap-2">
            <flux:button type="button" variant="ghost" wire:click="closeModal">Cancelar</flux:button>
            <flux:button type="submit" variant="primary">Guardar</flux:button>
        </div>
    </form>
</flux:modal>
