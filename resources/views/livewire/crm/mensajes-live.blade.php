<div class="space-y-3 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Mensajes WhatsApp</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Envía mensajes a clientes por WhatsApp (CRM)</p>
        </div>

        <x-cliente.search-input :clienteSearch="$clienteSearch" :clientes="$clientes" :selectedCliente="$selectedCliente" :isSearching="$isSearching ?? false" />

        @if ($selectedCliente)
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-4">
                <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-2">Enviar mensaje por WhatsApp</h3>
                @if ($selectedCliente->telefono)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Destino: {{ $selectedCliente->telefono }}</p>
                    <div class="flex gap-2">
                        <textarea wire:model="contenido" rows="3" placeholder="Escribe el mensaje..." class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                        @can('crm-mensajes.create')
                        <flux:button variant="primary" size="sm" wire:click="enviarWhatsApp" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="enviarWhatsApp">Enviar</span>
                            <span wire:loading wire:target="enviarWhatsApp">Enviando...</span>
                        </flux:button>
                        @endcan
                    </div>
                @else
                    <p class="text-sm text-amber-600 dark:text-amber-400">Este cliente no tiene teléfono registrado. Añade un teléfono en su ficha para enviar WhatsApp.</p>
                @endif
            </div>

            <div class="flex gap-3 items-center justify-end">
                <select wire:model.live="canalFilter" class="w-full max-w-[120px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Todos</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                </select>
                <select wire:model.live="perPage" class="w-full max-w-[100px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </select>
            </div>
            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                <table class="w-full text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Canal</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Destino</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Contenido</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($mensajes as $m)
                            <tr>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $m->created_at->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ ucfirst($m->canal) }}</td>
                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $m->destino }}</td>
                                <td class="px-4 py-2.5 text-zinc-900 dark:text-zinc-100 max-w-xs truncate">{{ Str::limit($m->contenido, 50) }}</td>
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                        {{ $m->estado === 'enviado' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                        {{ $m->estado === 'fallido' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                        {{ $m->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : '' }}
                                    ">{{ $m->estado }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No hay mensajes</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($mensajes->hasPages())
                <div class="mt-4 flex justify-end">{{ $mensajes->links() }}</div>
            @endif
        @else
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8 text-center text-zinc-500 dark:text-zinc-400">Selecciona un cliente para enviar mensajes y ver historial</div>
        @endif
    </div>
</div>
