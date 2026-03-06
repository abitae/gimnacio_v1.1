@php
    use Illuminate\Support\Facades\Storage;
@endphp

<div class="h-screen flex flex-col bg-zinc-50 dark:bg-zinc-900"
    x-data="{
        fullscreen: false,
        toggleFullscreen() {
            if (!document.fullscreenElement) {
                this.$refs.posPanel.requestFullscreen().then(() => { this.fullscreen = true; }).catch(() => {});
            } else {
                document.exitFullscreen().then(() => { this.fullscreen = false; }).catch(() => {});
            }
        }
    }"
    x-ref="posPanel"
    @fullscreenchange.window="fullscreen = !!document.fullscreenElement"
    :class="fullscreen ? 'fixed inset-0 z-50 h-screen flex flex-col bg-zinc-50 dark:bg-zinc-900' : ''">
    <!-- Header -->
    <div class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 px-4 py-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Punto de Venta</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Sistema de ventas integrado</p>
            </div>
            <div class="flex items-center gap-3">
                <flux:button
                    variant="ghost"
                    size="xs"
                    type="button"
                    @click="toggleFullscreen()"
                    class="shrink-0"
                    aria-label="Ver en pantalla completa"
                    title="Ver en pantalla completa">
                    <span x-show="!fullscreen" class="inline-flex"><flux:icon name="arrows-pointing-out" class="h-5 w-5" /></span>
                    <span x-show="fullscreen" class="inline-flex" x-cloak><flux:icon name="arrows-pointing-in" class="h-5 w-5" /></span>
                </flux:button>
                @if (!$modoCobroMembresiaClase)
                    <flux:button variant="primary" size="xs" icon="currency-dollar" color="green"
                        wire:click="activarModoCobroMembresiaClase">
                        Cobrar membresía / clase
                    </flux:button>
                @else
                    <flux:button variant="ghost" size="xs" wire:click="desactivarModoCobroMembresiaClase">
                        Volver a ventas
                    </flux:button>
                @endif
                @if ($this->cajaService->validarCajaAbierta(auth()->id()))
                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                        Caja Abierta
                    </span>
                @else
                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400">
                        Caja Cerrada
                    </span>
                @endif
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 flex overflow-hidden">
        @if ($modoCobroMembresiaClase)
            {{-- Panel Cobrar membresía/clase --}}
            <div class="flex-1 flex flex-col overflow-hidden bg-white dark:bg-zinc-800 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Cobrar membresía o clase</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 mb-4">Busca un cliente y selecciona el ítem con saldo pendiente a cobrar. El cobro se reporta a la caja abierta.</p>

                <div class="space-y-2 max-w-xl mb-4">
                    <label class="block text-xs font-medium text-zinc-700 dark:text-zinc-300">Buscar cliente</label>
                    <div class="relative">
                        <flux:input icon="magnifying-glass" type="search" size="xs"
                            wire:model.live.debounce.300ms="clienteSearchCobro"
                            placeholder="Nombre o documento..."
                            class="w-full" />
                        @if ($clienteSearchCobro && !$selectedClienteCobro)
                            @if ($clientesCobro && $clientesCobro->count() > 0)
                                <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white shadow-lg dark:border-zinc-700 dark:bg-zinc-800 max-h-60 overflow-y-auto">
                                    @foreach ($clientesCobro as $c)
                                        <button type="button" wire:click="selectClienteCobro({{ $c->id }})"
                                            class="w-full px-4 py-2 text-left text-xs hover:bg-zinc-50 dark:hover:bg-zinc-700">
                                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $c->nombres }} {{ $c->apellidos }}</span>
                                            <span class="text-zinc-500 dark:text-zinc-400 ml-1">· {{ $c->tipo_documento }} {{ $c->numero_documento }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif (strlen(trim($clienteSearchCobro)) >= 2)
                                <div class="absolute z-10 mt-1 w-full rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                                    <p class="text-xs text-zinc-500 dark:text-zinc-400">No se encontraron clientes</p>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                @if ($selectedClienteCobro)
                    <div class="flex items-center gap-3 mb-4 p-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800/50">
                        <span class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $selectedClienteCobro->nombres }} {{ $selectedClienteCobro->apellidos }}</span>
                        <flux:button variant="ghost" size="xs" wire:click="clearClienteCobro">Cambiar</flux:button>
                    </div>

                    @if (count($itemsConSaldo) > 0)
                        <div class="border border-zinc-200 dark:border-zinc-700 rounded-lg overflow-hidden">
                            <table class="w-full text-sm">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Concepto</th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tipo</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Saldo pendiente</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($itemsConSaldo as $item)
                                        <tr>
                                            <td class="px-4 py-2.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $item['nombre'] }}</td>
                                            <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">{{ $item['tipo'] === 'matricula' ? 'Matrícula' : 'Membresía' }}</td>
                                            <td class="px-4 py-2.5 text-right font-medium text-zinc-900 dark:text-zinc-100">S/ {{ number_format($item['saldo_pendiente'], 2) }}</td>
                                            <td class="px-4 py-2.5 text-right">
                                                <flux:button variant="primary" size="xs" color="green" wire:click="openCobroModal('{{ $item['tipo'] }}', {{ $item['id'] }})">
                                                    Cobrar
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Este cliente no tiene matrículas ni membresías con saldo pendiente.</p>
                    @endif
                @else
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Selecciona un cliente para ver sus ítems con saldo pendiente, o elige uno de la tabla siguiente.</p>
                @endif

                <!-- Clientes con deuda (en pestaña Cobrar membresía/clase) -->
                <div class="mt-6 border-t border-zinc-200 dark:border-zinc-700 pt-4">
                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-2">Clientes con deuda</h3>
                    @if ($clientesConDeuda->isNotEmpty())
                        <div class="overflow-x-auto max-h-56 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                            <table class="min-w-full text-xs">
                                <thead class="bg-zinc-50 dark:bg-zinc-800/50 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-1.5 text-left font-medium text-zinc-600 dark:text-zinc-400">Cliente</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-zinc-600 dark:text-zinc-400">Deuda</th>
                                        <th class="px-3 py-1.5 text-right font-medium text-zinc-600 dark:text-zinc-400">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                    @foreach ($clientesConDeuda as $c)
                                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                            <td class="px-3 py-1.5 text-zinc-900 dark:text-zinc-100">
                                                {{ $c->nombres }} {{ $c->apellidos }}
                                            </td>
                                            <td class="px-3 py-1.5 text-right font-medium text-amber-600 dark:text-amber-400">
                                                S/ {{ number_format($c->deuda_total, 2) }}
                                            </td>
                                            <td class="px-3 py-1.5 text-right">
                                                <flux:button variant="primary" size="xs" color="green" wire:click="selectClienteCobro({{ $c->id }})">
                                                    Cobrar
                                                </flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">No hay clientes con deuda.</p>
                    @endif
                </div>
            </div>
        @else
        <!-- Left Panel - Catálogo -->
        <div class="w-2/3 flex flex-col border-r border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
            <!-- Búsqueda y Filtros -->
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 space-y-3">
                <!-- Filtro de Tipo -->
                <flux:radio.group wire:model.live="tipoItem" variant="segmented">
                    <flux:radio value="producto" label="Productos" icon="cube" />
                    <flux:radio value="servicio" label="Servicios" icon="wrench-screwdriver" />
                </flux:radio.group>
                
                <div class="flex gap-2">
                    <div class="flex-1">
                        <flux:input 
                            type="text" 
                            wire:model.live.debounce.300ms="busqueda" 
                            :placeholder="$tipoItem === 'producto' ? 'Buscar productos...' : 'Buscar servicios...'" 
                            icon="magnifying-glass"
                            class="w-full"
                        />
                    </div>
                    <select wire:model.live="categoriaFiltro"
                        class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Todas las categorías</option>
                        @foreach ($categorias as $categoria)
                            <option value="{{ $categoria->id }}">{{ $categoria->nombre }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Resultados de Búsqueda o Productos por Categoría -->
            <div class="flex-1 overflow-y-auto p-4">
                @if (!empty($busqueda))
                    @if (!empty($resultadosBusqueda))
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                            @foreach ($resultadosBusqueda as $item)
                                <div 
                                    wire:click="agregarAlCarrito({{ json_encode($item) }})"
                                    class="cursor-pointer rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 hover:border-purple-500 hover:shadow-md transition-all">
                                    @if (isset($item['imagen']) && $item['imagen'])
                                        <img src="{{ Storage::url($item['imagen']) }}" alt="{{ $item['nombre'] }}" class="w-full h-32 object-cover rounded mb-2">
                                    @else
                                        <div class="w-full h-32 {{ $item['tipo'] === 'servicio' ? 'bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20' : 'bg-zinc-200 dark:bg-zinc-700' }} rounded mb-2 flex items-center justify-center">
                                            @if ($item['tipo'] === 'servicio')
                                                <flux:icon name="wrench-screwdriver" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                            @else
                                                <flux:icon name="cube" class="h-8 w-8 text-zinc-400" />
                                            @endif
                                        </div>
                                    @endif
                                    <div class="flex items-start justify-between mb-1">
                                        <span class="text-xs font-medium text-purple-600 dark:text-purple-400">
                                            {{ $item['codigo'] }}
                                        </span>
                                        @if (isset($item['stock']))
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                Stock: {{ $item['stock'] }}
                                            </span>
                                        @elseif (isset($item['duracion_minutos']) && $item['duracion_minutos'])
                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $item['duracion_minutos'] }} min
                                            </span>
                                        @endif
                                    </div>
                                    <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 mb-2">
                                        {{ $item['nombre'] }}
                                    </h3>
                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                        S/ {{ number_format($item['precio'], 2) }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No se encontraron resultados</p>
                        </div>
                    @endif
                @else
                    <!-- Items agrupados por categoría (Productos o Servicios) -->
                    @if ($itemsPorCategoria->isNotEmpty())
                        <div class="space-y-6">
                            @foreach ($itemsPorCategoria as $categoriaNombre => $items)
                                <div>
                                    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3 px-2">
                                        {{ $categoriaNombre }}
                                    </h3>
                                    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                                        @foreach ($items as $item)
                                            @if ($tipoItem === 'producto')
                                                <div 
                                                    wire:click="agregarAlCarrito({{ json_encode([
                                                        'tipo' => 'producto',
                                                        'id' => $item->id,
                                                        'codigo' => $item->codigo,
                                                        'nombre' => $item->nombre,
                                                        'precio' => $item->precio_venta,
                                                        'stock' => $item->stock_actual,
                                                        'imagen' => $item->imagen,
                                                    ]) }})"
                                                    class="cursor-pointer rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 hover:border-purple-500 hover:shadow-md transition-all">
                                                    @if ($item->imagen)
                                                        <img src="{{ Storage::url($item->imagen) }}" alt="{{ $item->nombre }}" class="w-full h-32 object-cover rounded mb-2">
                                                    @else
                                                        <div class="w-full h-32 bg-zinc-200 dark:bg-zinc-700 rounded mb-2 flex items-center justify-center">
                                                            <flux:icon name="cube" class="h-8 w-8 text-zinc-400" />
                                                        </div>
                                                    @endif
                                                    <div class="flex items-start justify-between mb-1">
                                                        <span class="text-xs font-medium text-purple-600 dark:text-purple-400">
                                                            {{ $item->codigo }}
                                                        </span>
                                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                            Stock: {{ $item->stock_actual }}
                                                        </span>
                                                    </div>
                                                    <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 mb-2">
                                                        {{ $item->nombre }}
                                                    </h3>
                                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                                        S/ {{ number_format($item->precio_venta, 2) }}
                                                    </p>
                                                </div>
                                            @else
                                                <div 
                                                    wire:click="agregarAlCarrito({{ json_encode([
                                                        'tipo' => 'servicio',
                                                        'id' => $item->id,
                                                        'codigo' => $item->codigo,
                                                        'nombre' => $item->nombre,
                                                        'precio' => $item->precio,
                                                        'duracion_minutos' => $item->duracion_minutos,
                                                    ]) }})"
                                                    class="cursor-pointer rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 hover:border-purple-500 hover:shadow-md transition-all">
                                                    <div class="w-full h-32 bg-gradient-to-br from-blue-100 to-indigo-100 dark:from-blue-900/20 dark:to-indigo-900/20 rounded mb-2 flex items-center justify-center">
                                                        <flux:icon name="wrench-screwdriver" class="h-8 w-8 text-blue-600 dark:text-blue-400" />
                                                    </div>
                                                    <div class="flex items-start justify-between mb-1">
                                                        <span class="text-xs font-medium text-purple-600 dark:text-purple-400">
                                                            {{ $item->codigo }}
                                                        </span>
                                                        @if ($item->duracion_minutos)
                                                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                                {{ $item->duracion_minutos }} min
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <h3 class="font-semibold text-sm text-zinc-900 dark:text-zinc-100 mb-2">
                                                        {{ $item->nombre }}
                                                    </h3>
                                                    <p class="text-lg font-bold text-zinc-900 dark:text-zinc-100">
                                                        S/ {{ number_format($item->precio, 2) }}
                                                    </p>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">
                                    No hay {{ $tipoItem === 'producto' ? 'productos' : 'servicios' }} disponibles
                                </p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500">
                                    Busca {{ $tipoItem === 'producto' ? 'productos' : 'servicios' }} o selecciona una categoría
                                </p>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <!-- Right Panel - Carrito -->
        <div class="w-1/3 flex flex-col bg-zinc-50 dark:bg-zinc-900">
            <!-- Cliente -->
            <div class="p-4 border-b border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300">Cliente</label>
                    <flux:button size="xs" variant="ghost" wire:click="$set('mostrarModalCliente', true)">
                        {{ $clienteSeleccionado ? 'Cambiar' : 'Seleccionar' }}
                    </flux:button>
                </div>
                @if ($clienteSeleccionado)
                    <div class="flex items-center justify-between p-2 rounded bg-zinc-100 dark:bg-zinc-700">
                        <div>
                            <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $clienteSeleccionado->nombres }} {{ $clienteSeleccionado->apellidos }}
                            </p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                {{ $clienteSeleccionado->tipo_documento }}: {{ $clienteSeleccionado->numero_documento }}
                            </p>
                        </div>
                        <flux:button size="xs" variant="ghost" color="red" wire:click="limpiarCliente">
                            ✕
                        </flux:button>
                    </div>
                @else
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 italic">Venta sin cliente (walk-in)</p>
                @endif
            </div>

            <!-- Carrito -->
            <div class="flex-1 overflow-y-auto p-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Carrito de Compras</h3>
                
                @if (!empty($carrito))
                    <div class="space-y-2">
                        @foreach ($carrito as $key => $item)
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3">
                                <div class="flex items-start justify-between mb-2">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-zinc-900 dark:text-zinc-100">
                                            {{ $item['nombre'] }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $item['codigo'] }}
                                        </p>
                                    </div>
                                    <flux:button size="xs" variant="ghost" color="red" wire:click="eliminarDelCarrito('{{ $key }}')">
                                        ✕
                                    </flux:button>
                                </div>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <flux:button size="xs" variant="ghost" wire:click="actualizarCantidad('{{ $key }}', {{ $item['cantidad'] - 1 }})">-</flux:button>
                                        <input 
                                            type="number" 
                                            wire:change="actualizarCantidad('{{ $key }}', $event.target.value)"
                                            value="{{ $item['cantidad'] }}"
                                            min="1"
                                            class="w-16 rounded border border-zinc-300 bg-white px-2 py-1 text-center text-xs text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                        <flux:button size="xs" variant="ghost" wire:click="actualizarCantidad('{{ $key }}', {{ $item['cantidad'] + 1 }})">+</flux:button>
                                    </div>
                                    <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                        S/ {{ number_format(($item['precio'] * $item['cantidad']) - ($item['descuento'] ?? 0), 2) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="flex items-center justify-center h-full">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">El carrito está vacío</p>
                    </div>
                @endif
            </div>

            <!-- Totales y Acciones -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 space-y-3">
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">Subtotal (IGV incluido):</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">S/ {{ number_format($this->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400">Descuento:</span>
                        <input 
                            type="number" 
                            wire:model.live="descuento"
                            step="0.01"
                            min="0"
                            class="w-24 rounded border border-zinc-300 bg-white px-2 py-1 text-right text-sm text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                    </div>
                    <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>Subtotal sin IGV:</span>
                        <span>S/ {{ number_format($this->subtotalSinIgv, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                        <span>IGV (18% incluido):</span>
                        <span>S/ {{ number_format($this->igv, 2) }}</span>
                    </div>
                    <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700">
                        <span class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Total:</span>
                        <span class="text-lg font-bold text-purple-600 dark:text-purple-400">S/ {{ number_format($this->total, 2) }}</span>
                    </div>
                </div>

                <div class="space-y-2">
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1 block">Tipo de Comprobante</label>
                        <select wire:model="tipoComprobante"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="ticket">Ticket</option>
                            <option value="boleta">Boleta</option>
                            <option value="factura">Factura</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mb-1 block">Método de Pago</label>
                        <select wire:model="metodoPago"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="efectivo">Efectivo</option>
                            <option value="tarjeta">Tarjeta</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="yape">Yape</option>
                            <option value="plin">Plin</option>
                        </select>
                    </div>
                </div>

                <div class="flex gap-2">
                    <flux:button variant="ghost" wire:click="limpiarCarrito" class="flex-1" wire:loading.attr="disabled">
                        Limpiar
                    </flux:button>
                    <flux:button 
                        color="purple" 
                        variant="primary" 
                        wire:click="procesarVenta" 
                        class="flex-1"
                        wire:loading.attr="disabled"
                        :disabled="empty($carrito)">
                        <span wire:loading.remove wire:target="procesarVenta">Procesar Venta</span>
                        <span wire:loading wire:target="procesarVenta">Procesando...</span>
                    </flux:button>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Modal Cobro membresía/clase -->
    <flux:modal name="cobro-modal" wire:model="mostrarModalCobro" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="procesarCobro">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Registrar cobro</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">El cobro se reportará a la caja abierta.</p>
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-2.5 dark:border-zinc-700 dark:bg-zinc-800">
                    <span class="text-xs text-zinc-500 dark:text-zinc-400">Saldo pendiente:</span>
                    <span class="font-semibold text-red-600 dark:text-red-400 block">S/ {{ number_format($saldoPendienteCobro, 2) }}</span>
                </div>
                <flux:input size="xs" wire:model.live.number="cobroFormData.monto_pago" label="Monto a pagar (S/)" type="number" step="0.01" min="0.01" required />
                @if ($cobroFormData['monto_pago'] > $saldoPendienteCobro)
                    <p class="text-xs text-red-600 dark:text-red-400">El monto no puede ser mayor al saldo pendiente.</p>
                @endif
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Método de pago</label>
                    <select wire:model="cobroFormData.metodo_pago"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="efectivo">Efectivo</option>
                        <option value="tarjeta">Tarjeta</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Comprobante</label>
                        <select wire:model="cobroFormData.comprobante_tipo"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="">Sin comprobante</option>
                            <option value="boleta">Boleta</option>
                            <option value="factura">Factura</option>
                            <option value="recibo">Recibo</option>
                        </select>
                    </div>
                    <div>
                        <flux:input size="xs" wire:model="cobroFormData.comprobante_numero" label="Número" placeholder="Opcional" />
                    </div>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:button variant="ghost" size="xs" type="button" wire:click="cerrarModalCobro">Cancelar</flux:button>
                <flux:button variant="primary" size="xs" type="submit" color="green" wire:loading.attr="disabled" wire:target="procesarCobro">
                    <span wire:loading.remove wire:target="procesarCobro">Registrar cobro</span>
                    <span wire:loading wire:target="procesarCobro">Procesando...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Modal Selección Cliente -->
    <flux:modal name="cliente-modal" wire:model="mostrarModalCliente" focusable flyout variant="floating" class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Seleccionar Cliente</h2>
            <div class="space-y-2 max-h-96 overflow-y-auto">
                @foreach ($clientes as $cliente)
                    <div 
                        wire:click="seleccionarCliente({{ $cliente->id }})"
                        class="cursor-pointer rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-3 hover:border-purple-500 transition-all">
                        <p class="font-medium text-sm text-zinc-900 dark:text-zinc-100">
                            {{ $cliente->nombres }} {{ $cliente->apellidos }}
                        </p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                            {{ $cliente->tipo_documento }}: {{ $cliente->numero_documento }}
                        </p>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 flex justify-end">
                <flux:button variant="ghost" wire:click="$set('mostrarModalCliente', false)">Cerrar</flux:button>
            </div>
        </div>
    </flux:modal>

    <!-- Modal Confirmación Venta -->
    <flux:modal name="confirmacion-modal" wire:model="mostrarModalConfirmacion" focusable flyout variant="floating" class="md:w-lg">
        @if ($ventaProcesada)
            <div class="p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-4">Venta Procesada Exitosamente</h2>
                <div class="space-y-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900 p-4">
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Número de Venta:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $ventaProcesada->numero_venta }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Comprobante:</span>
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">
                            {{ strtoupper($ventaProcesada->tipo_comprobante) }} {{ $ventaProcesada->serie_comprobante }}-{{ $ventaProcesada->numero_comprobante }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-600 dark:text-zinc-400">Total:</span>
                        <span class="font-bold text-purple-600 dark:text-purple-400">S/ {{ number_format($ventaProcesada->total, 2) }}</span>
                    </div>
                </div>
                <div class="mt-4 flex justify-end">
                    <flux:button variant="primary" wire:click="cerrarModalConfirmacion">Aceptar</flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
