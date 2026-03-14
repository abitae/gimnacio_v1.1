<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Matrículas de Clientes</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Gestiona las membresías y clases asignadas a los clientes</p>
            </div>
            <div class="flex gap-2">
                @can('clientes.create')
                <a href="{{ route('clientes.index') }}" wire:navigate>
                    <flux:button icon="plus" color="blue" variant="primary" size="xs" aria-label="Crear nuevo cliente">
                        Nuevo Cliente
                    </flux:button>
                </a>
                @endcan
            </div>
        </div>

        <!-- Flash Messages -->
        <div class="w-full">
        </div>

        <!-- Cliente Search -->
        <x-cliente.search-input 
            :clienteSearch="$clienteSearch" 
            :clientes="$clientes" 
            :selectedCliente="$selectedCliente" 
            :isSearching="$isSearching" />

        <!-- Two Column Layout -->
        @if ($selectedCliente)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Left Column: Cliente Information -->
                <div class="lg:col-span-1">
                    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3 space-y-2.5">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-2">
                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Información del Cliente</h3>
                            <flux:button variant="ghost" size="xs" wire:click="clearClienteSelection" aria-label="Limpiar selección">
                                ✕
                            </flux:button>
                        </div>

                        <!-- Foto y Deuda -->
                        <div class="flex items-center gap-3">
                            @if ($selectedCliente->foto)
                                <div class="flex-shrink-0">
                                    <img src="{{ asset('storage/' . $selectedCliente->foto) }}" alt="Foto del cliente"
                                        class="w-16 h-16 rounded-full object-cover border border-zinc-200 dark:border-zinc-700">
                                </div>
                            @else
                                <div class="flex-shrink-0">
                                    <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center">
                                        <span class="text-lg text-zinc-500 dark:text-zinc-400">
                                            {{ strtoupper(substr($selectedCliente->nombres, 0, 1) . substr($selectedCliente->apellidos, 0, 1)) }}
                                        </span>
                                    </div>
                                </div>
                            @endif
                            
                            <!-- Deuda Total -->
                            @php
                                $deudaTotal = $selectedCliente->deuda_total;
                            @endphp
                            <div class="flex-1">
                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400 mb-0.5">Deuda Total</p>
                                @if ($deudaTotal > 0)
                                    <p class="text-sm font-bold text-red-600 dark:text-red-400">
                                        S/ {{ number_format($deudaTotal, 2) }}
                                    </p>
                                @else
                                    <p class="text-sm font-semibold text-green-600 dark:text-green-400">
                                        Sin deuda
                                    </p>
                                @endif
                            </div>
                        </div>

                        <!-- Información Básica -->
                        <div class="space-y-1.5">
                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Nombre</p>
                                <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $selectedCliente->nombres }} {{ $selectedCliente->apellidos }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Documento</p>
                                <p class="text-xs text-zinc-900 dark:text-zinc-100">
                                    {{ $selectedCliente->tipo_documento }}: {{ $selectedCliente->numero_documento }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">Estado</p>
                                <span
                                    class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $selectedCliente->estado_cliente === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($selectedCliente->estado_cliente === 'inactivo' ? 'bg-gray-100 text-gray-800 dark:bg-gray-900/20 dark:text-gray-400' : 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400') }}">
                                    {{ ucfirst($selectedCliente->estado_cliente) }}
                                </span>
                            </div>

                            <x-cliente.info-field label="Teléfono" :value="$selectedCliente->telefono" />
                            <x-cliente.info-field label="Email" :value="$selectedCliente->email" />
                            <x-cliente.info-field label="Dirección" :value="$selectedCliente->direccion" />
                            @if ($selectedCliente->biotime_state)
                                <x-cliente.info-field label="BioTime" value="Sincronizado" />
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Right Column: Matrículas -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Tabs -->
                    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="$set('activeTab', 'membresias')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $activeTab === 'membresias' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Membresías
                        </button>
                        <button type="button" wire:click="$set('activeTab', 'clases')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $activeTab === 'clases' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Clases
                        </button>
                    </div>

                    <!-- Filters and Actions -->
                    <div class="flex gap-3 items-center justify-between">
                        @can('cliente-matriculas.create')
                        <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateModal"
                            wire:loading.attr="disabled" wire:target="openCreateModal" aria-label="Nueva matrícula">
                            <span wire:loading.remove wire:target="openCreateModal">Agregar {{ $activeTab === 'membresias' ? 'Membresía' : 'Clase' }}</span>
                            <span wire:loading wire:target="openCreateModal">Cargando...</span>
                        </flux:button>
                        @endcan

                        <div class="flex gap-3 items-center">
                            <div class="w-32">
                                <select wire:model.live="estadoFilter"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                    aria-label="Filtrar por estado">
                                    <option value="">Todos</option>
                                    <option value="activa">Activa</option>
                                    <option value="vencida">Vencida</option>
                                    <option value="cancelada">Cancelada</option>
                                    <option value="congelada">Congelada</option>
                                    <option value="completada">Completada</option>
                                </select>
                            </div>
                            <div class="w-28">
                                <select wire:model.live="perPage"
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                    aria-label="Elementos por página">
                                    <option value="10">10</option>
                                    <option value="20">20</option>
                                    <option value="50">50</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Matrículas Table -->
                    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-zinc-50 dark:bg-zinc-900">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            {{ $activeTab === 'membresias' ? 'Membresía' : 'Clase' }}
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Fecha Matrícula
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Fecha Inicio
                                        </th>
                                        @if ($activeTab === 'membresias')
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Fecha Fin
                                            </th>
                                        @else
                                            <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Sesiones
                                            </th>
                                        @endif
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Precio Final
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Saldo
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Estado
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                    @forelse ($matriculas as $matricula)
                                        <tr>
                                            <td class="px-4 py-2.5 text-xs">
                                                <span class="font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $matricula->esMembresia() ? ($matricula->membresia->nombre ?? 'N/A') : ($matricula->clase->nombre ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $matricula->fecha_matricula?->format('d/m/Y') ?? '-' }}
                                            </td>
                                            <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                {{ $matricula->fecha_inicio->format('d/m/Y') }}
                                            </td>
                                            @if ($activeTab === 'membresias')
                                                <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $matricula->fecha_fin ? $matricula->fecha_fin->format('d/m/Y') : '-' }}
                                                </td>
                                            @else
                                                <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                    {{ $matricula->sesiones_usadas ?? 0 }} / {{ $matricula->sesiones_totales ?? '-' }}
                                                </td>
                                            @endif
                                            <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                                S/ {{ number_format($matricula->precio_final, 2) }}
                                            </td>
                                            <td class="px-4 py-2.5 text-xs">
                                                @php
                                                    $saldoPendiente = (float) $matricula->saldo_pendiente_actual;
                                                @endphp
                                                <div class="space-y-0.5">
                                                    <div class="text-zinc-900 dark:text-zinc-100">
                                                        <span class="text-zinc-500 dark:text-zinc-400 text-[10px]">
                                                            {{ $matricula->usaPlanCuotas() ? 'Monto Financiado:' : 'Monto a Pagar:' }}
                                                        </span>
                                                        <span class="font-medium">
                                                            S/ {{ number_format($matricula->usaPlanCuotas() ? $matricula->monto_financiado : $matricula->precio_final, 2) }}
                                                        </span>
                                                    </div>
                                                    <div class="text-zinc-900 dark:text-zinc-100">
                                                        <span class="text-zinc-500 dark:text-zinc-400 text-[10px]">Saldo Pendiente:</span>
                                                        <span class="font-medium {{ $saldoPendiente > 0 ? 'text-red-600 dark:text-red-400' : 'text-green-600 dark:text-green-400' }}">
                                                            S/ {{ number_format($saldoPendiente, 2) }}
                                                        </span>
                                                    </div>
                                                    @if ($matricula->usaPlanCuotas())
                                                        <div class="text-zinc-500 dark:text-zinc-400 text-[10px]">
                                                            Cuota inicial: S/ {{ number_format($matricula->cuota_inicial_monto ?? 0, 2) }}
                                                        </div>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs">
                                                <span
                                                    class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $matricula->estado === 'activa' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($matricula->estado === 'vencida' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400' : ($matricula->estado === 'cancelada' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : ($matricula->estado === 'completada' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400'))) }}">
                                                    {{ ucfirst($matricula->estado) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2.5 text-xs">
                                                <div class="flex gap-1 flex-wrap">
                                                    @can('cliente-matriculas.view')
                                                    <a href="{{ route('cliente-matriculas.cuotas', $matricula) }}" wire:navigate aria-label="Cuotas">
                                                        <flux:button variant="ghost" size="xs" icon="currency-dollar">Cuotas</flux:button>
                                                    </a>
                                                    @endcan
                                                    @if ($matricula->estado !== 'completada')
                                                        @can('cliente-matriculas.update')
                                                        <flux:button variant="ghost" size="xs" icon="pencil"
                                                            wire:click="openEditModal({{ $matricula->id }})" aria-label="Editar">
                                                        </flux:button>
                                                        @endcan
                                                        @can('cliente-matriculas.delete')
                                                        <flux:button variant="ghost" size="xs" icon="trash" color="red"
                                                            wire:click="openDeleteModal({{ $matricula->id }})" aria-label="Eliminar">
                                                        </flux:button>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8"
                                                class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                                No se encontraron {{ $activeTab === 'membresias' ? 'membresías' : 'clases' }} para este cliente
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    @if ($matriculas->hasPages())
                        <div class="mt-4 flex justify-end">
                            {{ $matriculas->links() }}
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8">
                <div class="flex flex-col items-center justify-center text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Busca y selecciona un cliente para ver sus matrículas
                    </p>
                </div>
            </div>
        @endif

        <!-- Membresías próximas a vencer (siempre visible) -->
        <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden">
            <div class="border-b border-zinc-200 dark:border-zinc-700 px-4 py-2">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Membresías próximas a vencer</h2>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Matrículas de membresía que vencen en los próximos 30 días</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800/50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Cliente</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Membresía</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Fecha fin</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Días restantes</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Registró cliente</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Matriculó membresía</th>
                            <th scope="col" class="px-4 py-2 text-right text-xs font-medium text-zinc-600 dark:text-zinc-400 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @forelse ($matriculaMembresiasProximasAVencer as $m)
                            @php
                                $diasRestantes = \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($m->fecha_fin), false);
                            @endphp
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                <td class="px-4 py-2 text-xs text-zinc-900 dark:text-zinc-100">
                                    {{ $m->cliente ? $m->cliente->nombres . ' ' . $m->cliente->apellidos : '—' }}
                                </td>
                                <td class="px-4 py-2 text-xs text-zinc-700 dark:text-zinc-300">
                                    {{ $m->membresia ? $m->membresia->nombre : '—' }}
                                </td>
                                <td class="px-4 py-2 text-xs text-zinc-700 dark:text-zinc-300">
                                    {{ \Carbon\Carbon::parse($m->fecha_fin)->format('d/m/Y') }}
                                </td>
                                <td class="px-4 py-2 text-xs">
                                    @if ($diasRestantes <= 0)
                                        <span class="text-amber-600 dark:text-amber-400">Vence hoy</span>
                                    @else
                                        <span class="text-zinc-700 dark:text-zinc-300">{{ $diasRestantes }} días</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-xs text-zinc-700 dark:text-zinc-300">
                                    {{ $m->cliente && $m->cliente->registroPor ? $m->cliente->registroPor->name : '—' }}
                                </td>
                                <td class="px-4 py-2 text-xs text-zinc-700 dark:text-zinc-300">
                                    {{ $m->asesor ? $m->asesor->name : '—' }}
                                </td>
                                <td class="px-4 py-2 text-right">
                                    <flux:button size="xs" variant="primary" wire:click="openRenovarMembresia({{ $m->cliente_id }}, {{ $m->id }})">
                                        Renovar membresía
                                    </flux:button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                    No hay membresías que venzan en los próximos 30 días
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create/Edit Modal -->
    <flux:modal name="create-edit-modal" wire:model="modalState.create" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit.prevent="save">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $clienteMatriculaId ? 'Editar Matrícula' : 'Nueva Matrícula' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $clienteMatriculaId ? 'Modifica la información de la matrícula' : 'Asigna una nueva ' . ($formData['tipo'] === 'membresia' ? 'membresía' : 'clase') . ' al cliente' }}
                    </p>
                </div>

                @if (!$clienteMatriculaId)
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="formData.tipo"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="membresia">Membresía</option>
                            <option value="clase">Clase</option>
                        </select>
                        <flux:error name="formData.tipo" />
                    </div>
                @endif

                @if ($formData['tipo'] === 'membresia')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Membresía <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="formData.membresia_id"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="">Selecciona una membresía</option>
                            @foreach ($membresiasActivas as $membresia)
                                <option value="{{ $membresia->id }}">{{ $membresia->nombre }} - S/
                                    {{ number_format($membresia->precio_base, 2) }}</option>
                            @endforeach
                        </select>
                        <flux:error name="formData.membresia_id" />
                    </div>
                @else
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Clase <span class="text-red-500">*</span>
                        </label>
                        <select wire:model.live="formData.clase_id"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="">Selecciona una clase</option>
                            @foreach ($clasesActivas as $clase)
                                <option value="{{ $clase->id }}">{{ $clase->nombre }} - S/
                                    {{ number_format($clase->obtenerPrecio(), 2) }}</option>
                            @endforeach
                        </select>
                        <flux:error name="formData.clase_id" />
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <flux:input size="xs" wire:model="formData.fecha_matricula" label="Fecha Matrícula" type="date"
                            required />
                        @error('formData.fecha_matricula')
                            <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <flux:input size="xs" wire:model="formData.fecha_inicio" label="Fecha Inicio" type="date"
                            required />
                        @error('formData.fecha_inicio')
                            <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    @if ($formData['tipo'] === 'membresia')
                        <div>
                            <flux:input size="xs" wire:model="formData.fecha_fin" label="Fecha Fin" type="date" required />
                            <flux:error name="formData.fecha_fin" />
                        </div>
                    @else
                        <div>
                            <flux:input size="xs" wire:model.number="formData.sesiones_totales" label="Sesiones Totales"
                                type="number" min="1" />
                            <flux:error name="formData.sesiones_totales" />
                        </div>
                    @endif
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <flux:input size="xs" wire:model.live.number="formData.precio_lista" label="Precio Lista (S/)"
                            type="number" step="0.01" min="0" required />
                        <flux:error name="formData.precio_lista" />
                    </div>

                    <div>
                        <flux:input size="xs" wire:model.live.number="formData.descuento_monto" label="Descuento (S/)"
                            type="number" step="0.01" min="0" />
                        <flux:error name="formData.descuento_monto" />
                    </div>

                    <div>
                        <flux:input size="xs" wire:model.number="formData.precio_final" label="Precio Final (S/)"
                            type="number" step="0.01" min="0" readonly />
                        <flux:error name="formData.precio_final" />
                    </div>
                </div>

                @if ($formData['tipo'] === 'membresia' && $membresiaPermiteCuotas)
                    @php
                        $cuotaInicial = (float) ($formData['cuota_inicial_monto'] ?? 0);
                        $saldoFinanciado = max(0, (float) ($formData['precio_final'] ?? 0) - $cuotaInicial);
                        $numeroCuotas = max(1, (int) ($formData['numero_cuotas'] ?: 1));
                        $montoEstimadoCuota = $numeroCuotas > 0 ? round($saldoFinanciado / $numeroCuotas, 2) : 0;
                    @endphp
                    <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Pago de Membresía</h3>
                            @if ($clienteMatriculaId && ($formData['modalidad_pago'] ?? 'contado') === 'cuotas')
                                <span class="text-[11px] text-zinc-500 dark:text-zinc-400">El plan ya fue generado</span>
                            @endif
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    Modalidad de Pago
                                </label>
                                <select wire:model.live="formData.modalidad_pago"
                                    @disabled($clienteMatriculaId && ($formData['modalidad_pago'] ?? 'contado') === 'cuotas')
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 disabled:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:disabled:bg-zinc-700">
                                    <option value="contado">Contado</option>
                                    <option value="cuotas">Cuotas</option>
                                </select>
                                <flux:error name="formData.modalidad_pago" />
                            </div>
                            <div>
                                <flux:input size="xs" wire:model.number="formData.cuota_inicial_monto" label="Cuota Inicial (S/)"
                                    type="number" step="0.01" min="0"
                                    @disabled(($formData['modalidad_pago'] ?? 'contado') !== 'cuotas' || ($clienteMatriculaId && ($formData['modalidad_pago'] ?? 'contado') === 'cuotas')) />
                                <flux:error name="formData.cuota_inicial_monto" />
                            </div>
                        </div>

                        @if (($formData['modalidad_pago'] ?? 'contado') === 'cuotas')
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                <div>
                                    <flux:input size="xs" wire:model.number="formData.numero_cuotas" label="Número de Cuotas"
                                        type="number" min="2" max="60" @disabled($clienteMatriculaId) />
                                    <flux:error name="formData.numero_cuotas" />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                        Frecuencia
                                    </label>
                                    <select wire:model="formData.frecuencia_cuotas"
                                        @disabled($clienteMatriculaId)
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 disabled:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:disabled:bg-zinc-700">
                                        <option value="semanal">Semanal</option>
                                        <option value="quincenal">Quincenal</option>
                                        <option value="mensual">Mensual</option>
                                    </select>
                                    <flux:error name="formData.frecuencia_cuotas" />
                                </div>
                                <div>
                                    <flux:input size="xs" wire:model="formData.fecha_inicio_plan_cuotas"
                                        label="Inicio del Plan" type="date" @disabled($clienteMatriculaId) />
                                    <flux:error name="formData.fecha_inicio_plan_cuotas" />
                                </div>
                            </div>
                            <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900/40">
                                <div class="text-xs">
                                    <span class="text-zinc-500 dark:text-zinc-400">Saldo financiado:</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100"> S/ {{ number_format($saldoFinanciado, 2) }}</span>
                                </div>
                                <div class="text-xs">
                                    <span class="text-zinc-500 dark:text-zinc-400">Cuota estimada:</span>
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100"> S/ {{ number_format($montoEstimadoCuota, 2) }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Estado
                        </label>
                        <select wire:model="formData.estado"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="activa">Activa</option>
                            <option value="vencida">Vencida</option>
                            <option value="cancelada">Cancelada</option>
                            <option value="congelada">Congelada</option>
                            <option value="completada">Completada</option>
                        </select>
                        <flux:error name="formData.estado" />
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Canal de Venta
                        </label>
                        <select wire:model="formData.canal_venta"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="presencial">Presencial</option>
                            <option value="online">Online</option>
                            <option value="telefonico">Telefónico</option>
                            <option value="referido">Referido</option>
                        </select>
                        <flux:error name="formData.canal_venta" />
                    </div>
                </div>

                @if ($formData['estado'] === 'cancelada')
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Motivo de Cancelación
                        </label>
                        <textarea wire:model="formData.motivo_cancelacion" rows="2"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                        <flux:error name="formData.motivo_cancelacion" />
                    </div>
                @endif
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" size="xs" wire:click="closeModal" type="button">
                        Cancelar
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled"
                    wire:target="save">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="save" />
                        <span wire:loading.remove wire:target="save">{{ $clienteMatriculaId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    @can('cliente-matriculas.delete')
    <!-- Delete Modal -->
    <flux:modal name="delete-modal" wire:model="modalState.delete" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Eliminar Matrícula
            </h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                ¿Estás seguro de que deseas eliminar esta matrícula? Esta acción no se puede deshacer.
            </p>
        </div>

        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" size="xs" wire:click="closeModal" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            <flux:button variant="danger" size="xs" wire:click="delete" type="button"
                wire:loading.attr="disabled" wire:target="delete">
                <span class="inline-flex items-center gap-1.5">
                <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="delete" />
                <span wire:loading.remove wire:target="delete">Eliminar</span>
                <span wire:loading wire:target="delete">Eliminando...</span>
            </span>
            </flux:button>
        </div>
    </flux:modal>
    @endcan

</div>
