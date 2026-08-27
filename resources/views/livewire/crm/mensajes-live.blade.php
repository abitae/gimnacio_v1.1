<div class="space-y-3 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <x-crm.subnav />

        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Mensajes WhatsApp</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Envía mensajes a clientes o leads por WhatsApp (CRM)</p>
        </div>

        <div class="flex gap-2">
            <flux:button size="sm" variant="{{ $contactMode === 'cliente' ? 'primary' : 'ghost' }}" wire:click="$set('contactMode', 'cliente')">Clientes</flux:button>
            <flux:button size="sm" variant="{{ $contactMode === 'lead' ? 'primary' : 'ghost' }}" wire:click="$set('contactMode', 'lead')">Leads</flux:button>
        </div>

        @if ($contactMode === 'cliente')
            <x-cliente.search-input :clienteSearch="$clienteSearch" :clientes="$clientes" :selectedCliente="$selectedCliente" :isSearching="$isSearching ?? false" />
        @else
            <div class="relative">
                <flux:input icon="magnifying-glass" type="search" wire:model.live.debounce.300ms="leadSearch" placeholder="Buscar lead por nombre o teléfono..." />
                @if($leads->isNotEmpty())
                <ul class="absolute z-20 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 max-h-48 overflow-y-auto">
                    @foreach($leads as $lead)
                    <li>
                        <button type="button" wire:click="selectLead({{ $lead->id }})" class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            {{ $lead->nombre_completo }} · {{ $lead->telefono }}
                        </button>
                    </li>
                    @endforeach
                </ul>
                @endif
            </div>
            @if($selectedLead)
            <div class="flex items-center justify-between rounded-lg border border-zinc-200 dark:border-zinc-700 px-3 py-2 text-sm">
                <span>{{ $selectedLead->nombre_completo }} ({{ $selectedLead->telefono }})</span>
                <flux:button size="xs" variant="ghost" wire:click="clearLead">Cambiar</flux:button>
            </div>
            @endif
        @endif

        @if (($contactMode === 'cliente' && $selectedCliente) || ($contactMode === 'lead' && $selectedLead))
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-4">
                <h3 class="text-sm font-medium text-zinc-900 dark:text-zinc-100 mb-2">Enviar mensaje por WhatsApp</h3>
                @php
                    $telefono = $contactMode === 'cliente' ? $selectedCliente?->telefono : ($selectedLead?->whatsapp ?: $selectedLead?->telefono);
                @endphp
                @if ($telefono)
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Destino: {{ $telefono }}</p>
                    <div class="flex gap-2">
                        <textarea wire:model="contenido" rows="3" placeholder="Escribe el mensaje..." class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                        @canany(['crm_mensaje.enviar', 'crm_mensaje.crear'])
                        <flux:button variant="primary" size="sm" wire:click="enviarWhatsApp" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="enviarWhatsApp">Enviar</span>
                            <span wire:loading wire:target="enviarWhatsApp">Enviando...</span>
                        </flux:button>
                        @endcanany
                    </div>
                @else
                    <p class="text-sm text-amber-600 dark:text-amber-400">Este contacto no tiene teléfono registrado.</p>
                @endif
            </div>

            <div class="flex gap-3 items-center justify-end">
                <flux:select wire:model.live="canalFilter" size="xs" class="w-auto" aria-label="Filtrar por canal">
                    <option value="">Todos</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="email">Email</option>
                    <option value="sms">SMS</option>
                </flux:select>
                <flux:select wire:model.live="perPage" size="xs" class="w-auto" aria-label="Resultados por página">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="25">25</option>
                </flux:select>
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
                                    <x-crm.status-badge :status="$m->estado" type="mensaje" />
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state message="No hay mensajes" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($mensajes->hasPages())
                <div class="mt-4 flex justify-end">{{ $mensajes->links() }}</div>
            @endif
        @else
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8 text-center text-zinc-500 dark:text-zinc-400">
                Selecciona un {{ $contactMode === 'lead' ? 'lead' : 'cliente' }} para enviar mensajes y ver historial
            </div>
        @endif
    </div>
</div>
