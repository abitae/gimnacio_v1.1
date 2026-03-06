<div class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Campañas CRM</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Campañas de renovación, reactivación y captación</p>
        </div>
        @can('crm.create')
        <flux:button size="sm" variant="primary" wire:click="openCreate">Nueva campaña</flux:button>
        @endcan
    </div>

    <flux:input wire:model.live.debounce.300ms="search" placeholder="Buscar campaña..." class="max-w-xs" />

    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Nombre</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Tipo</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Estado</th>
                    <th class="px-4 py-2 text-left font-medium text-zinc-500">Creado por</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse($campaigns as $c)
                <tr>
                    <td class="px-4 py-2">
                        <a href="{{ route('crm.campaigns.show', $c->id) }}" wire:navigate class="font-medium text-zinc-900 dark:text-zinc-100 hover:underline">{{ $c->nombre }}</a>
                    </td>
                    <td class="px-4 py-2">{{ \App\Models\Crm\Campaign::TIPOS[$c->tipo] ?? $c->tipo }}</td>
                    <td class="px-4 py-2">{{ \App\Models\Crm\Campaign::ESTADOS[$c->estado] ?? $c->estado }}</td>
                    <td class="px-4 py-2">{{ $c->createdBy?->name ?? '—' }}</td>
                    <td class="px-4 py-2">
                        @can('crm.update')
                        <flux:button size="xs" variant="ghost" wire:click="openEdit({{ $c->id }})">Editar</flux:button>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-6 text-center text-zinc-500">No hay campañas</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-2 border-t border-zinc-200 dark:border-zinc-700">{{ $campaigns->links() }}</div>
    </div>

    <flux:modal name="campaign-form" wire:model="modalForm" focusable flyout variant="floating" class="md:w-lg">
        <div>
            <flux:heading size="lg">{{ $editingId ? 'Editar campaña' : 'Nueva campaña' }}</flux:heading>
            <form wire:submit="save" class="mt-4 space-y-3">
                <flux:field>
                    <flux:label>Nombre <span class="text-red-500">*</span></flux:label>
                    <flux:input wire:model="nombre" required />
                </flux:field>
                <flux:field>
                    <flux:label>Tipo</flux:label>
                    <select wire:model="tipo" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                        @foreach(\App\Models\Crm\Campaign::TIPOS as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="estado" class="flux-input rounded-lg w-full border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-800 px-3 py-2">
                        @foreach(\App\Models\Crm\Campaign::ESTADOS as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="closeModal">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</div>
