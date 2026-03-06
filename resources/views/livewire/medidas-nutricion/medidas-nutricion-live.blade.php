<div class="space-y-3 border border-zinc-200 rounded-lg p-3">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Medidas y Nutrici?n</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Gestiona evaluaciones, citas y asignaci?n de trainers</p>
            </div>
            <div class="flex gap-2">
                <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateEvaluacionModal"
                    wire:loading.attr="disabled" wire:target="openCreateEvaluacionModal" aria-label="Nueva evaluaci?n">
                    <span wire:loading.remove wire:target="openCreateEvaluacionModal">Nueva evaluaci?n</span>
                    <span wire:loading wire:target="openCreateEvaluacionModal">Cargando...</span>
                </flux:button>
                <flux:button icon="plus" color="blue" variant="primary" size="xs" wire:click="openCreateCitaModal"
                    wire:loading.attr="disabled" wire:target="openCreateCitaModal" aria-label="Nueva cita">
                    <span wire:loading.remove wire:target="openCreateCitaModal">Nueva cita</span>
                    <span wire:loading wire:target="openCreateCitaModal">Cargando...</span>
                </flux:button>
                @if ($selectedClienteId && $ultimaEvaluacion)
                    <flux:dropdown position="bottom" align="end">
                        <flux:button icon="arrow-down-tray" variant="ghost" size="xs" aria-label="Descargar">
                            Descargar
                        </flux:button>
                        <flux:menu>
                            <flux:menu.item wire:click="abrirPreviewReporte({{ $ultimaEvaluacion->id }})" icon="document-text">
                                Reporte de Evaluación
                            </flux:menu.item>
                        </flux:menu>
                    </flux:dropdown>
                @endif
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
                    <x-cliente.profile-card :cliente="$selectedCliente" />
                </div>

                <!-- Right Column: Content -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Tabs -->
                    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="$set('activeTab', 'medidas')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $activeTab === 'medidas' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Ficha Salud
                        </button>
                        <button type="button" wire:click="$set('activeTab', 'historial')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $activeTab === 'historial' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Historial de evaluaciones
                        </button>
                        <button type="button" wire:click="$set('activeTab', 'agenda')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $activeTab === 'agenda' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Agenda
                        </button>
                        <button type="button" wire:click="$set('activeTab', 'trainers')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $activeTab === 'trainers' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Socios con trainer general
                        </button>
                    </div>

                    <!-- Tab Content: Medidas -->
                    @if ($activeTab === 'medidas')
                        @if ($ultimaEvaluacion)
                            <div class="space-y-3">
                                <!-- Composici?n Corporal -->
                                <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                                    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2 mb-3">
                                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">COMPOSICI?N CORPORAL</h3>
                                        <span class="ml-auto text-[10px] text-zinc-500 dark:text-zinc-400">
                                            {{ $ultimaEvaluacion->created_at->format('d/m/Y g:i A') }}
                                        </span>
                                    </div>
                                    <x-medidas.composicion-corporal :evaluacion="$ultimaEvaluacion" />
                                </div>

                                <!-- Circunferencias -->
                                <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                                    <div class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2 mb-3">
                                        <svg class="w-5 h-5 text-zinc-600 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">CIRCUNFERENCIAS (cm)</h3>
                                        <span class="ml-auto text-[10px] text-zinc-500 dark:text-zinc-400">
                                            {{ $ultimaEvaluacion->created_at->format('d/m/Y g:i A') }}
                                        </span>
                                    </div>
                                    <x-medidas.circunferencias :evaluacion="$ultimaEvaluacion" />
                                </div>

                                <!-- Pr?xima Evaluaci?n -->
                                @if ($ultimaEvaluacion->fecha_proxima_evaluacion)
                                    <div class="flex items-center justify-end gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                        <span class="inline-flex items-center gap-1">
                                            <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                            Pr?xima evaluaci?n: {{ $ultimaEvaluacion->fecha_proxima_evaluacion->format('d M. Y') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8">
                                <div class="flex flex-col items-center justify-center text-center">
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        No hay evaluaciones registradas para este cliente
                                    </p>
                                    <flux:button icon="plus" color="purple" variant="primary" size="xs" wire:click="openCreateEvaluacionModal"
                                        class="mt-4">
                                        Crear Primera Evaluaci?n
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    @endif

                    <!-- Tab Content: Historial -->
                    @if ($activeTab === 'historial')
                        <div class="space-y-3">
                            <!-- Filters -->
                            <div class="flex gap-3 items-center justify-end">
                                <div class="w-32">
                                    <select wire:model.live="estadoFilter"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                        aria-label="Filtrar por estado">
                                        <option value="">Todos</option>
                                        <option value="pendiente">Pendiente</option>
                                        <option value="completada">Completada</option>
                                        <option value="cancelada">Cancelada</option>
                                    </select>
                                </div>
                                <div class="w-28">
                                    <select wire:model.live="perPage"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                        aria-label="Elementos por p?gina">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">FECHA EVALUACI?N</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">PROX. EVALUACI?N</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">OBJETIVO</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">PESO</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">NUTRICIONISTA</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                            @forelse ($evaluaciones as $evaluacion)
                                                <tr>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                        {{ $evaluacion->created_at->format('d/m/Y') }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                        {{ $evaluacion->fecha_proxima_evaluacion ? $evaluacion->fecha_proxima_evaluacion->format('d/m/Y') : '-' }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                                        {{ $evaluacion->objetivo ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                                        {{ $evaluacion->peso ? number_format($evaluacion->peso, 2) . ' kg' : '-' }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                        {{ $evaluacion->nutricionista->name ?? '-' }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs">
                                                        <div class="flex gap-1">
                                                            <flux:button variant="ghost" size="xs" icon="pencil"
                                                                wire:click="openEditEvaluacionModal({{ $evaluacion->id }})" aria-label="Editar">
                                                            </flux:button>
                                                            <flux:button variant="ghost" size="xs" icon="trash" color="red"
                                                                wire:click="openDeleteEvaluacionModal({{ $evaluacion->id }})" aria-label="Eliminar">
                                                            </flux:button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                                        No se encontraron evaluaciones
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination -->
                            @if ($evaluaciones->hasPages())
                                <div class="mt-4 flex justify-end">
                                    {{ $evaluaciones->links() }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Tab Content: Agenda -->
                    @if ($activeTab === 'agenda')
                        <div class="space-y-3">
                            <!-- Filters -->
                            <div class="flex gap-3 items-center justify-end">
                                <div class="w-32">
                                    <select wire:model.live="tipoFilter"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                        aria-label="Filtrar por tipo">
                                        <option value="">Todos</option>
                                        <option value="evaluacion">Evaluaci?n</option>
                                        <option value="consulta_nutricional">Consulta Nutricional</option>
                                        <option value="seguimiento">Seguimiento</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>
                                <div class="w-32">
                                    <select wire:model.live="estadoFilter"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                        aria-label="Filtrar por estado">
                                        <option value="">Todos</option>
                                        <option value="programada">Programada</option>
                                        <option value="confirmada">Confirmada</option>
                                        <option value="en_curso">En Curso</option>
                                        <option value="completada">Completada</option>
                                        <option value="cancelada">Cancelada</option>
                                        <option value="no_asistio">No Asisti?</option>
                                    </select>
                                </div>
                                <div class="w-28">
                                    <select wire:model.live="perPage"
                                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                                        aria-label="Elementos por p?gina">
                                        <option value="10">10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Table -->
                            <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Fecha/Hora</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Tipo</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Profesional</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Estado</th>
                                                <th class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-800">
                                            @forelse ($citas as $cita)
                                                <tr>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                        {{ $cita->fecha_hora->format('d/m/Y H:i') }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-900 dark:text-zinc-100">
                                                        {{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs text-zinc-600 dark:text-zinc-400">
                                                        @if ($cita->nutricionista)
                                                            {{ $cita->nutricionista->name }}
                                                        @elseif ($cita->trainerUser)
                                                            {{ $cita->trainerUser->name }}
                                                        @else
                                                            -
                                                        @endif
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs">
                                                        <span
                                                            class="inline-flex rounded-full px-1.5 py-0.5 text-xs font-medium {{ $cita->estado === 'completada' ? 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400' : ($cita->estado === 'cancelada' ? 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400' : ($cita->estado === 'programada' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400')) }}">
                                                            {{ ucfirst(str_replace('_', ' ', $cita->estado)) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-4 py-2.5 text-xs">
                                                        <div class="flex gap-1">
                                                            @if (in_array($cita->estado, ['programada', 'confirmada', 'en_curso']))
                                                                <flux:button variant="ghost" size="xs" icon="x-mark" color="red"
                                                                    wire:click="cancelarCita({{ $cita->id }})" aria-label="Cancelar">
                                                                </flux:button>
                                                            @endif
                                                            @if (in_array($cita->estado, ['programada', 'confirmada', 'en_curso']))
                                                                <flux:button variant="ghost" size="xs" icon="check"
                                                                    wire:click="completarCita({{ $cita->id }})" aria-label="Completar">
                                                                </flux:button>
                                                            @endif
                                                            <flux:button variant="ghost" size="xs" icon="pencil"
                                                                wire:click="openEditCitaModal({{ $cita->id }})" aria-label="Editar">
                                                            </flux:button>
                                                            <flux:button variant="ghost" size="xs" icon="trash" color="red"
                                                                wire:click="openDeleteCitaModal({{ $cita->id }})" aria-label="Eliminar">
                                                            </flux:button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="px-4 py-8 text-center text-xs text-zinc-500 dark:text-zinc-400">
                                                        No se encontraron citas
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Pagination -->
                            @if ($citas->hasPages())
                                <div class="mt-4 flex justify-end">
                                    {{ $citas->links() }}
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Tab Content: Trainers -->
                    @if ($activeTab === 'trainers')
                        <div class="space-y-3">
                            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">Trainer General</h3>
                                </div>
                                @if ($selectedCliente->trainerUser)
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between p-2 bg-zinc-50 dark:bg-zinc-700 rounded">
                                            <div>
                                                <p class="text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                                    {{ $selectedCliente->trainerUser->name }}
                                                </p>
                                                <p class="text-[10px] text-zinc-500 dark:text-zinc-400">
                                                    Usuario con rol trainer
                                                </p>
                                            </div>
                                            <flux:button variant="ghost" size="xs" icon="x-mark" color="red"
                                                wire:click="removerTrainer({{ $selectedCliente->id }})" aria-label="Remover trainer">
                                            </flux:button>
                                        </div>
                                    </div>
                                @else
                                    <div class="space-y-2">
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2">Sin Trainer asignado</p>
                                        <select wire:model="trainerAsignacionId"
                                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                            <option value="">Seleccionar trainer</option>
                                            @foreach ($trainers as $trainer)
                                                <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                                            @endforeach
                                        </select>
                                        <flux:button variant="primary" size="xs" wire:click="asignarTrainer({{ $selectedCliente->id }}, {{ $trainerAsignacionId }})"
                                            wire:loading.attr="disabled" :disabled="!$trainerAsignacionId">
                                            Asignar
                                        </flux:button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8">
                <div class="flex flex-col items-center justify-center text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Busca y selecciona un cliente para ver sus evaluaciones y citas
                    </p>
                </div>
            </div>
        @endif
    </div>

    <!-- Evaluaci?n Modal -->
    <flux:modal name="evaluacion-modal" wire:model="modalState.evaluacion" focusable flyout variant="floating" class="md:w-2xl">
        <form wire:submit.prevent="saveEvaluacion">
            <div class="space-y-3 p-4 max-h-[80vh] overflow-y-auto">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $evaluacionId ? 'Editar Evaluaci?n' : 'Nueva Evaluaci?n' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $evaluacionId ? 'Modifica la informaci?n de la evaluaci?n' : 'Registra una nueva evaluaci?n de medidas y nutrici?n' }}
                    </p>
                </div>

                <!-- Peso y Estatura -->
                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model.live.number="evaluacionFormData.peso" label="Peso (kg)"
                        type="number" step="0.01" min="0" />
                    <flux:input size="xs" wire:model.live.number="evaluacionFormData.estatura" label="Estatura (m)"
                        type="number" step="0.01" min="0.5" max="3" />
                </div>
                <div>
                    <flux:input size="xs" wire:model.number="evaluacionFormData.imc" label="IMC"
                        type="number" step="0.01" readonly />
                </div>

                <!-- Composici?n Corporal -->
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Composici?n Corporal</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input size="xs" wire:model.number="evaluacionFormData.porcentaje_grasa" label="% Grasa"
                            type="number" step="0.01" min="0" max="100" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_grasa" label="Masa Grasa (kg)"
                            type="number" step="0.01" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.porcentaje_musculo" label="% M?sculo"
                            type="number" step="0.01" min="0" max="100" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_muscular" label="Masa Muscular (kg)"
                            type="number" step="0.01" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_osea" label="Masa ?sea (kg)"
                            type="number" step="0.01" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_residual" label="Masa Residual (kg)"
                            type="number" step="0.01" min="0" />
                    </div>
                </div>

                <!-- Circunferencias -->
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Circunferencias (cm)</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.estatura" label="Estatura"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cuello" label="Cuello"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.brazo_normal" label="Brazo Normal"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.brazo_contraido" label="Brazo Contra?do"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.torax" label="T?rax"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cintura" label="Cintura"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cintura_baja" label="Cintura Baja"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cadera" label="Cadera"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.muslo" label="Muslo"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.gluteos" label="Gl?teos"
                            type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.pantorrilla" label="Pantorrilla"
                            type="number" step="0.1" min="0" />
                    </div>
                </div>

                <!-- Otros datos -->
                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model="evaluacionFormData.presion_arterial" label="Presi?n Arterial"
                        placeholder="Ej: 120/80" />
                    <flux:input size="xs" wire:model.number="evaluacionFormData.frecuencia_cardiaca" label="Frecuencia Card?aca"
                        type="number" min="0" max="300" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Objetivo
                    </label>
                    <select wire:model="evaluacionFormData.objetivo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="DEPORTES ? SALUD">DEPORTES ? SALUD</option>
                        <option value="P?RDIDA DE PESO">P?RDIDA DE PESO</option>
                        <option value="GANANCIA DE MASA">GANANCIA DE MASA</option>
                        <option value="TONIFICACI?N">TONIFICACI?N</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Nutricionista
                        </label>
                        <select wire:model="evaluacionFormData.nutricionista_id"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="">Sin nutricionista</option>
                            @foreach ($nutricionistas as $nutricionista)
                                <option value="{{ $nutricionista->id }}">{{ $nutricionista->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <flux:input size="xs" wire:model="evaluacionFormData.fecha_proxima_evaluacion" label="Pr?xima Evaluaci?n"
                            type="date" />
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Estado
                    </label>
                    <select wire:model="evaluacionFormData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="pendiente">Pendiente</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Observaciones
                    </label>
                    <textarea wire:model="evaluacionFormData.observaciones" rows="3"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" size="xs" wire:click="closeEvaluacionModal" type="button">
                        Cancelar
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled"
                    wire:target="saveEvaluacion">
                    <span wire:loading.remove wire:target="saveEvaluacion">{{ $evaluacionId ? 'Actualizar' : 'Crear' }}</span>
                    <span wire:loading wire:target="saveEvaluacion">Guardando...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Evaluaci?n Modal -->
    <flux:modal name="delete-evaluacion-modal" wire:model="modalState.delete_evaluacion" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Eliminar Evaluaci?n
            </h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                ?Est?s seguro de que deseas eliminar esta evaluaci?n? Esta acci?n no se puede deshacer.
            </p>
        </div>

        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" size="xs" wire:click="closeEvaluacionModal" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            <flux:button variant="danger" size="xs" wire:click="deleteEvaluacion" type="button"
                wire:loading.attr="disabled" wire:target="deleteEvaluacion">
                <span wire:loading.remove wire:target="deleteEvaluacion">Eliminar</span>
                <span wire:loading wire:target="deleteEvaluacion">Eliminando...</span>
            </flux:button>
        </div>
    </flux:modal>

    <!-- Cita Modal -->
    <flux:modal name="cita-modal" wire:model="modalState.cita" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit.prevent="saveCita">
            <div class="space-y-3 p-4">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $citaId ? 'Editar Cita' : 'Nueva Cita' }}
                    </h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $citaId ? 'Modifica la informaci?n de la cita' : 'Programa una nueva cita para el cliente' }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select wire:model="citaFormData.tipo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="evaluacion">Evaluaci?n</option>
                        <option value="consulta_nutricional">Consulta Nutricional</option>
                        <option value="seguimiento">Seguimiento</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <flux:input size="xs" wire:model="citaFormData.fecha_hora" label="Fecha y Hora" type="datetime-local"
                            required />
                    </div>
                    <div>
                        <flux:input size="xs" wire:model.number="citaFormData.duracion_minutos" label="Duraci?n (min)"
                            type="number" min="15" max="480" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Nutricionista
                        </label>
                        <select wire:model="citaFormData.nutricionista_id"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="">Sin nutricionista</option>
                            @foreach ($nutricionistas as $nutricionista)
                                <option value="{{ $nutricionista->id }}">{{ $nutricionista->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                            Trainer
                        </label>
                        <select wire:model="citaFormData.trainer_user_id"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="">Sin trainer</option>
                            @foreach ($trainers as $trainer)
                                <option value="{{ $trainer->id }}">{{ $trainer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Estado
                    </label>
                    <select wire:model="citaFormData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="programada">Programada</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="en_curso">En Curso</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="no_asistio">No Asisti?</option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Observaciones
                    </label>
                    <textarea wire:model="citaFormData.observaciones" rows="2"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" size="xs" wire:click="closeCitaModal" type="button">
                        Cancelar
                    </flux:button>
                </flux:modal.close>
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled"
                    wire:target="saveCita">
                    <span wire:loading.remove wire:target="saveCita">{{ $citaId ? 'Actualizar' : 'Crear' }}</span>
                    <span wire:loading wire:target="saveCita">Guardando...</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <!-- Delete Cita Modal -->
    <flux:modal name="delete-cita-modal" wire:model="modalState.delete_cita" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                Eliminar Cita
            </h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">
                ?Est?s seguro de que deseas eliminar esta cita? Esta acci?n no se puede deshacer.
            </p>
        </div>

        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" size="xs" wire:click="closeCitaModal" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            <flux:button variant="danger" size="xs" wire:click="deleteCita" type="button"
                wire:loading.attr="disabled" wire:target="deleteCita">
                <span wire:loading.remove wire:target="deleteCita">Eliminar</span>
                <span wire:loading wire:target="deleteCita">Eliminando...</span>
            </flux:button>
        </div>
    </flux:modal>

    {{-- Modal previsualización reporte --}}
    <flux:modal name="reporte-preview-modal" wire:model="modalState.reporte_preview" focusable class="md:max-w-4xl" variant="floating">
        <flux:heading>Reporte de Evaluación</flux:heading>
        <flux:subheading>Previsualiza el PDF y luego imprime o descarga.</flux:subheading>
        @if($evaluacionIdReporte)
            <div class="mt-4 flex flex-col gap-3">
                <div class="min-h-[400px] w-full overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800">
                    <iframe src="{{ route('reportes.evaluacion.preview', $evaluacionIdReporte) }}" class="h-[70vh] w-full min-h-[400px] border-0" title="Vista previa del reporte"></iframe>
                </div>
                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="cerrarPreviewReporte">Cerrar</flux:button>
                    <a href="{{ route('reportes.evaluacion.preview', $evaluacionIdReporte) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">Imprimir (nueva pestaña)</a>
                    <a href="{{ route('reportes.evaluacion.descargar', $evaluacionIdReporte) }}" target="_blank" download class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">Descargar PDF</a>
                </div>
            </div>
        @endif
    </flux:modal>

</div>
