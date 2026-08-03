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
            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ $exportUrl }}" target="_blank" rel="noopener"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-xs font-medium text-emerald-700 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300 dark:hover:bg-emerald-900/30">
                    <flux:icon name="table-cells" class="size-4" />
                    {{ __('Exportar Excel') }}
                </a>
                <a href="{{ route('clientes.perfil.index') }}" wire:navigate>
                    <flux:button icon="user-circle" color="purple" variant="primary" size="sm" type="button">
                        {{ __('Abrir perfil') }}
                    </flux:button>
                </a>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <div class="w-full min-w-[12rem] sm:w-44">
                <flux:input icon="magnifying-glass" type="search" size="xs"
                    wire:model.live.debounce.300ms="search"
                    placeholder="{{ __('Nombre, apellidos, documento o correo…') }}"
                    class="w-full"
                    aria-label="{{ __('Buscar clientes por nombre, documento o email') }}" />
            </div>
            <div class="w-full min-w-[10rem] sm:w-36">
                <flux:input icon="identification" type="search" size="xs"
                    wire:model.live.debounce.300ms="codigoSearch"
                    placeholder="{{ __('Solo código…') }}"
                    class="w-full"
                    aria-label="{{ __('Filtrar por código interno del cliente') }}" />
            </div>
            <div class="w-full min-w-[10rem] sm:w-44">
                <select wire:model.live="asesorFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Asesor') }}">
                    <option value="">{{ __('Todos los asesores') }}</option>
                    @foreach ($asesores as $asesor)
                        <option value="{{ $asesor->id }}">{{ $asesor->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full min-w-[10rem] sm:w-40">
                <select wire:model.live="vigenciaFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Vigencia') }}">
                    <option value="">{{ __('Toda vigencia') }}</option>
                    <option value="activos">{{ __('Con plan activo') }}</option>
                    <option value="por_vencer">{{ __('Por vencer') }}</option>
                    <option value="por_iniciar">{{ __('Por iniciar') }}</option>
                    <option value="inactivos">{{ __('Clientes inactivos') }}</option>
                </select>
            </div>
            @if ($vigenciaFilter === 'por_vencer')
                <div class="w-full min-w-[8rem] sm:w-28">
                    <select wire:model.live="ventanaDias"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                        aria-label="{{ __('Ventana por vencer') }}">
                        <option value="7">7 {{ __('días') }}</option>
                        <option value="15">15 {{ __('días') }}</option>
                        <option value="30">30 {{ __('días') }}</option>
                        <option value="45">45 {{ __('días') }}</option>
                    </select>
                </div>
            @endif
            <div class="w-full min-w-[10rem] sm:w-36">
                <select wire:model.live="estadoFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Estado del cliente') }}">
                    <option value="">{{ __('Todo estado') }}</option>
                    <option value="activo">{{ __('Activo') }}</option>
                    <option value="inactivo">{{ __('Inactivo') }}</option>
                    <option value="suspendido">{{ __('Suspendido') }}</option>
                </select>
            </div>
            <div class="w-full min-w-[10rem] sm:w-44">
                <select wire:model.live="membresiaFilter"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Tipo de membresía') }}">
                    <option value="">{{ __('Todas las membresías') }}</option>
                    @foreach ($membresias as $membresia)
                        <option value="{{ $membresia->id }}">{{ $membresia->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-28">
                <select wire:model.live="perPage"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    aria-label="{{ __('Por página') }}">
                    <option value="10">10</option>
                    <option value="15">15</option>
                    <option value="20">20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4 xl:grid-cols-8"
            wire:loading.delay.class="opacity-60"
            wire:target="search,codigoSearch,asesorFilter,vigenciaFilter,ventanaDias,estadoFilter,membresiaFilter,perPage">
            <div class="rounded-lg border border-indigo-100 bg-indigo-50/50 p-2 dark:border-indigo-900/50 dark:bg-indigo-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Total clientes') }}</div>
                <div class="text-base font-bold tabular-nums text-indigo-700 dark:text-indigo-300">{{ $resumen['total'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-emerald-100 bg-emerald-50/60 p-2 dark:border-emerald-900/50 dark:bg-emerald-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-emerald-600 dark:text-emerald-400">{{ __('Clientes activos') }}</div>
                <div class="text-base font-bold tabular-nums text-emerald-700 dark:text-emerald-300">{{ $resumen['activos'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-zinc-200 bg-zinc-50/80 p-2 dark:border-zinc-700 dark:bg-zinc-800/50">
                <div class="text-[10px] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ __('Clientes inactivos') }}</div>
                <div class="text-base font-bold tabular-nums text-zinc-900 dark:text-zinc-100">{{ $resumen['inactivos'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-2 dark:border-amber-900/50 dark:bg-amber-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-amber-600 dark:text-amber-400">{{ __('Por vencer') }}</div>
                <div class="text-base font-bold tabular-nums text-amber-700 dark:text-amber-300">{{ $resumen['clientes_por_vencer'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-sky-100 bg-sky-50/60 p-2 dark:border-sky-900/50 dark:bg-sky-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-sky-600 dark:text-sky-400">{{ __('Por iniciar') }}</div>
                <div class="text-base font-bold tabular-nums text-sky-700 dark:text-sky-300">{{ $resumen['membresias_por_iniciar'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-fuchsia-100 bg-fuchsia-50/60 p-2 dark:border-fuchsia-900/50 dark:bg-fuchsia-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-fuchsia-600 dark:text-fuchsia-400">{{ __('Traspasos') }}</div>
                <div class="text-base font-bold tabular-nums text-fuchsia-700 dark:text-fuchsia-300">{{ $resumen['traspasos'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-lime-100 bg-lime-50/60 p-2 dark:border-lime-900/50 dark:bg-lime-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-lime-700 dark:text-lime-400">{{ __('Asistencias') }}</div>
                <div class="text-base font-bold tabular-nums text-lime-700 dark:text-lime-300">{{ $resumen['asistencias'] ?? 0 }}</div>
            </div>
            <div class="rounded-lg border border-rose-100 bg-rose-50/60 p-2 dark:border-rose-900/50 dark:bg-rose-950/30">
                <div class="text-[10px] font-medium uppercase tracking-wide text-rose-600 dark:text-rose-400">{{ __('Inasistencias') }}</div>
                <div class="text-base font-bold tabular-nums text-rose-700 dark:text-rose-300">{{ $resumen['inasistencias'] ?? 0 }}</div>
            </div>
        </div>

        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700"
            wire:loading.delay.class="opacity-60 pointer-events-none"
            wire:target="search,codigoSearch,asesorFilter,vigenciaFilter,ventanaDias,estadoFilter,membresiaFilter,perPage">
            <table class="w-full min-w-[820px] text-sm">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Cliente') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Código') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Documento') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Contacto') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Asesor') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Estado') }}</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Deuda') }}</th>
                        <th class="px-3 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                    @forelse ($clientes as $cliente)
                        @php
                            $estadoClienteBadge = match ($cliente->estado_cliente) {
                                'activo' => 'green',
                                'inactivo' => 'zinc',
                                default => 'red',
                            };
                            $deudaTotal = (float) $cliente->deuda_total;
                        @endphp
                        <tr wire:key="cliente-row-{{ $cliente->id }}">
                            <td class="px-3 py-2">
                                <div class="flex min-w-0 max-w-[14rem] items-center gap-2">
                                    @if ($cliente->foto)
                                        <img src="{{ asset('storage/' . $cliente->foto) }}" alt=""
                                            class="size-9 shrink-0 rounded-full border border-zinc-200 object-cover dark:border-zinc-600">
                                    @else
                                        <div class="flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-200 text-xs text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">
                                            {{ strtoupper(substr($cliente->nombres, 0, 1) . substr($cliente->apellidos, 0, 1)) }}
                                        </div>
                                    @endif
                                    <span class="truncate font-medium text-zinc-900 dark:text-zinc-100">
                                        {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-3 py-2 tabular-nums font-semibold text-violet-700 dark:text-violet-300">
                                {{ filled($cliente->codigo) ? $cliente->codigo : '—' }}
                            </td>
                            <td class="px-3 py-2 text-zinc-700 dark:text-zinc-300">
                                <span class="whitespace-nowrap">{{ $cliente->tipo_documento }}</span>
                                <span class="tabular-nums">{{ $cliente->numero_documento }}</span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="max-w-[12rem] space-y-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                    @if (filled($cliente->email))
                                        <p class="truncate" title="{{ $cliente->email }}">{{ $cliente->email }}</p>
                                    @endif
                                    @if (filled($cliente->telefono))
                                        <p class="tabular-nums text-zinc-500 dark:text-zinc-500">{{ $cliente->telefono }}</p>
                                    @endif
                                    @if (! filled($cliente->email) && ! filled($cliente->telefono))
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="block max-w-[10rem] truncate text-xs text-zinc-700 dark:text-zinc-300" title="{{ $cliente->asesor_nombre ?? '' }}">
                                    {{ $cliente->asesor_nombre ?? '—' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <flux:badge :color="$estadoClienteBadge">{{ ucfirst($cliente->estado_cliente) }}</flux:badge>
                            </td>
                            <td class="px-3 py-2">
                                @if ($deudaTotal > 0)
                                    <flux:badge color="red" class="tabular-nums text-xs">S/ {{ number_format($deudaTotal, 2) }}</flux:badge>
                                @else
                                    <flux:badge color="green" class="text-xs">{{ __('Sin deuda') }}</flux:badge>
                                @endif
                            </td>
                                <td class="px-3 py-2 text-right">
                                <x-ui.table-actions>
                                    <flux:button size="xs" variant="ghost" icon="document-text" type="button"
                                        wire:click="abrirContrato({{ $cliente->id }})"
                                        title="{{ __('Contrato de membresía') }}">
                                        {{ __('Contrato') }}
                                    </flux:button>
                                    @if (filled($cliente->telefono))
                                        <flux:button size="xs" variant="ghost" icon="chat-bubble-left-right" type="button"
                                            wire:click="enviarContratoPorWhatsApp({{ $cliente->id }})"
                                            title="{{ __('Enviar contrato por WhatsApp') }}"
                                            aria-label="{{ __('Enviar contrato por WhatsApp') }}" />
                                    @endif
                                    <flux:button size="xs" variant="primary" icon="user-circle" type="button"
                                        wire:click="verPerfil({{ $cliente->id }})">
                                        {{ __('Ver perfil') }}
                                    </flux:button>
                                </x-ui.table-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                {{ __('No hay clientes') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="flex justify-end">
            {{ $clientes->links() }}
        </div>
    </div>

    <flux:modal wire:model="mostrarModalContrato" class="w-[96vw] max-w-[1200px]">
        <div class="p-4">
            <div class="mb-3 flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Contrato de membresía') }}</h2>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Vista previa del documento para impresión, descarga o envío por WhatsApp.') }}</p>
                </div>
                @if ($clienteIdContrato)
                    <div class="flex flex-wrap items-center gap-2">
                        <flux:button size="xs" variant="ghost" icon="chat-bubble-left-right" type="button"
                            wire:click="enviarContratoPorWhatsApp({{ $clienteIdContrato }})">
                            {{ __('Enviar por WhatsApp') }}
                        </flux:button>
                        <a href="{{ route('clientes.contrato-membresia.pdf', ['cliente' => $clienteIdContrato]) }}"
                            target="_blank" rel="noopener"
                            class="inline-flex items-center gap-1 rounded-lg border border-zinc-200 px-3 py-1.5 text-xs font-medium text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-200 dark:hover:bg-zinc-800">
                            {{ __('Abrir en pestaña nueva') }}
                        </a>
                    </div>
                @endif
            </div>
            @if ($clienteIdContrato)
                <iframe
                    src="{{ route('clientes.contrato-membresia.pdf', ['cliente' => $clienteIdContrato]) }}"
                    class="h-[82vh] w-full rounded-xl border border-zinc-200 dark:border-zinc-700"
                    title="{{ __('Contrato de membresía') }}"
                ></iframe>
            @endif
        </div>
    </flux:modal>
</div>
