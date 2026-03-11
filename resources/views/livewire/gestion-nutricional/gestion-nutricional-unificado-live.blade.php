<div class="space-y-3 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700">
    <div class="flex h-full w-full flex-1 flex-col gap-3">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Gestión Nutricional</h1>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Medidas, nutrición y citas por cliente</p>
            </div>
            @if ($selectedClienteId && $ultimaEvaluacion)
                <flux:dropdown position="bottom" align="end">
                    <flux:button icon="arrow-down-tray" variant="ghost" size="xs" aria-label="Descargar">Descargar
                    </flux:button>
                    <flux:menu>
                        <flux:menu.item wire:click="abrirPreviewReporte({{ $ultimaEvaluacion->id }})"
                            icon="document-text">
                            Reporte de Evaluación</flux:menu.item>
                        @if($selectedCliente && filled($selectedCliente->telefono))
                            <flux:menu.item wire:click="enviarReportePorWhatsApp({{ $ultimaEvaluacion->id }})"
                                icon="envelope">
                                Enviar por WhatsApp</flux:menu.item>
                            <flux:menu.item wire:click="abrirChatWhatsApp" icon="chat-bubble-left-right">
                                Abrir chat WhatsApp</flux:menu.item>
                        @else
                            <flux:menu.item icon="envelope"
                                wire:click="mostrarErrorSinTelefono"
                                title="Añade el teléfono del cliente en su ficha para enviar por WhatsApp">
                                Enviar por WhatsApp (sin teléfono)</flux:menu.item>
                            <flux:menu.item icon="chat-bubble-left-right" wire:click="mostrarErrorSinTelefono"
                                title="Añade el teléfono del cliente para abrir chat">
                                Abrir chat WhatsApp (sin teléfono)</flux:menu.item>
                        @endif
                    </flux:menu>
                </flux:dropdown>
            @endif
        </div>
        <div class="w-full">
        </div>

        <x-cliente.search-input :clienteSearch="$clienteSearch" :clientes="$clientes" :selectedCliente="$selectedCliente" :isSearching="$isSearching" />

        @if ($selectedCliente)
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                <!-- Perfil del Cliente (Izquierda) -->
                <div class="lg:col-span-1">
                    <x-cliente.profile-card :cliente="$selectedCliente" hideActions="true" />
                </div>

                <!-- Contenido Principal (Derecha) -->
                <div class="lg:col-span-2 space-y-3">
                    <!-- Pestañas Principales (Todas en un solo nivel) -->
                    <div class="flex gap-2 border-b border-zinc-200 dark:border-zinc-700">
                        <button type="button" wire:click="$set('mainTab', 'ficha_salud')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $mainTab === 'ficha_salud' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Ficha Salud
                        </button>
                        <button type="button" wire:click="$set('mainTab', 'nutricion')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $mainTab === 'nutricion' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Nutrición
                        </button>
                        <button type="button" wire:click="$set('mainTab', 'citas')"
                            class="px-4 py-2 text-xs font-medium transition-colors {{ $mainTab === 'citas' ? 'text-purple-600 border-b-2 border-purple-600 dark:text-purple-400 dark:border-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                            Citas
                        </button>
                    </div>

                    <!-- Contenido de Pestaña: FICHA SALUD -->
                    @if ($mainTab === 'ficha_salud')
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Ficha de Salud</h2>
                                <div class="flex gap-2">
                                    @can('gestion-nutricional.create')
                                    <flux:button icon="plus" color="purple" variant="primary" size="xs"
                                        wire:click="openCreateEvaluacionModal" wire:loading.attr="disabled"
                                        wire:target="openCreateEvaluacionModal" aria-label="Nueva evaluación">
                                        <span wire:loading.remove wire:target="openCreateEvaluacionModal">Nueva
                                            evaluación</span>
                                        <span wire:loading wire:target="openCreateEvaluacionModal">Cargando...</span>
                                    </flux:button>
                                    @endcan
                                </div>
                            </div>
                            @if ($ultimaEvaluacion)
                                @php
                                    $circunferencias = $ultimaEvaluacion->circunferencias ?? [];
                                @endphp
                                <div class="space-y-3">
                                    <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Medidas</h2>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Card 1: COMPOSICIÓN CORPORAL -->
                                        <div
                                            class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                                            <div class="mb-3 flex items-start gap-2">
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-600 dark:bg-zinc-700">
                                                    <flux:icon name="clipboard-document-list"
                                                        class="size-5 text-zinc-600 dark:text-zinc-300" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h3
                                                        class="text-xs font-bold uppercase tracking-wide text-zinc-900 dark:text-zinc-100">
                                                        Composición corporal</h3>
                                                    <p class="mt-0.5 text-[10px] text-zinc-500 dark:text-zinc-400">
                                                        {{ $ultimaEvaluacion->created_at->format('d/m/Y g:i A') }}</p>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-4 sm:flex-row">
                                                <!-- Gráfico a la izquierda -->
                                                <div class="flex shrink-0 items-center justify-center sm:order-1">
                                                    <div class="relative h-44 w-44">
                                                        <canvas x-data="composicionChartData({{ json_encode($ultimaEvaluacion->composicion_corporal) }}, {{ $ultimaEvaluacion->peso ?? 0 }})" x-init="initChart($el)"
                                                            class="h-full w-full"></canvas>
                                                    </div>
                                                </div>
                                                <!-- Datos a la derecha -->
                                                <div
                                                    class="min-w-0 flex-1 flex flex-col justify-center gap-1.5 text-xs sm:order-2">
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Peso</span>
                                                        <span
                                                            class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->peso ? number_format($ultimaEvaluacion->peso, 1) . 'kg' : '-' }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span
                                                            class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                            <span class="size-2 shrink-0 rounded-sm bg-blue-500"></span>
                                                            Masa Muscular
                                                        </span>
                                                        <span class="text-right">
                                                            @if ($ultimaEvaluacion->porcentaje_musculo)
                                                                <span
                                                                    class="text-zinc-500 dark:text-zinc-400">{{ number_format($ultimaEvaluacion->porcentaje_musculo, 2) }}%</span>
                                                            @endif
                                                            <span
                                                                class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->masa_muscular ? number_format($ultimaEvaluacion->masa_muscular, 1) . 'kg' : '0kg' }}</span>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span
                                                            class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                            <span class="size-2 shrink-0 rounded-sm bg-blue-500"></span>
                                                            Porcentaje de Grasa
                                                        </span>
                                                        <span
                                                            class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->porcentaje_grasa ? number_format($ultimaEvaluacion->porcentaje_grasa, 1) . '%' : '0%' }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span
                                                            class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                            <span
                                                                class="size-2 shrink-0 rounded-sm bg-zinc-400 dark:bg-zinc-500"></span>
                                                            Masa Grasa
                                                        </span>
                                                        <span class="text-right">
                                                            <span
                                                                class="text-zinc-500 dark:text-zinc-400">{{ $ultimaEvaluacion->masa_grasa ? number_format(($ultimaEvaluacion->masa_grasa / max($ultimaEvaluacion->peso ?? 1, 1)) * 100, 1) . '%' : '0%' }}</span>
                                                            <span
                                                                class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->masa_grasa ? number_format($ultimaEvaluacion->masa_grasa, 1) . 'kg' : '0kg' }}</span>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span
                                                            class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                            <span
                                                                class="size-2 shrink-0 rounded-sm bg-zinc-400 dark:bg-zinc-500"></span>
                                                            Masa Ósea
                                                        </span>
                                                        <span class="text-right">
                                                            <span
                                                                class="text-zinc-500 dark:text-zinc-400">{{ $ultimaEvaluacion->masa_osea ? number_format(($ultimaEvaluacion->masa_osea / max($ultimaEvaluacion->peso ?? 1, 1)) * 100, 1) . '%' : '0%' }}</span>
                                                            <span
                                                                class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->masa_osea ? number_format($ultimaEvaluacion->masa_osea, 1) . 'kg' : '0kg' }}</span>
                                                        </span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span
                                                            class="flex items-center gap-1.5 text-zinc-600 dark:text-zinc-400">
                                                            <span
                                                                class="size-2 shrink-0 rounded-sm bg-zinc-400 dark:bg-zinc-500"></span>
                                                            Masa Residual
                                                        </span>
                                                        <span class="text-right">
                                                            <span
                                                                class="text-zinc-500 dark:text-zinc-400">{{ $ultimaEvaluacion->masa_residual ? number_format(($ultimaEvaluacion->masa_residual / max($ultimaEvaluacion->peso ?? 1, 1)) * 100, 1) . '%' : '0%' }}</span>
                                                            <span
                                                                class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->masa_residual ? number_format($ultimaEvaluacion->masa_residual, 1) . 'kg' : '0kg' }}</span>
                                                        </span>
                                                    </div>
                                                    <div
                                                        class="flex items-center justify-between gap-4 border-t border-zinc-100 pt-1.5 dark:border-zinc-700">
                                                        <span class="text-zinc-600 dark:text-zinc-400">IMC</span>
                                                        <span
                                                            class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $ultimaEvaluacion->imc ? number_format($ultimaEvaluacion->imc, 2) : '-' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Card 2: CIRCUNFERENCIAS (cm) -->
                                        <div
                                            class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
                                            <div class="mb-3 flex items-start gap-2">
                                                <div
                                                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-zinc-200 bg-white dark:border-zinc-600 dark:bg-zinc-700">
                                                    <flux:icon name="clipboard-document-list"
                                                        class="size-5 text-zinc-600 dark:text-zinc-300" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h3
                                                        class="text-xs font-bold uppercase tracking-wide text-zinc-900 dark:text-zinc-100">
                                                        Circunferencias (cm)</h3>
                                                    <p class="mt-0.5 text-[10px] text-zinc-500 dark:text-zinc-400">
                                                        {{ $ultimaEvaluacion->created_at->format('d/m/Y g:i A') }}</p>
                                                </div>
                                            </div>
                                            <div class="flex flex-col gap-4 sm:flex-row">
                                                <!-- Imagen a la izquierda -->
                                                <div class="flex shrink-0 items-center justify-center sm:order-1">
                                                    @php
                                                        $imagenSexo =
                                                            ($selectedCliente->sexo ?? '') === 'femenino'
                                                                ? asset('img/MALE-Photoroom.png')
                                                                : asset('img/MEN-Photoroom.png');
                                                    @endphp
                                                    <img src="{{ $imagenSexo }}" alt="Silueta"
                                                        class="h-52 w-32 object-contain opacity-80 dark:opacity-70">
                                                </div>
                                                <!-- Datos a la derecha -->
                                                <div
                                                    class="min-w-0 flex-1 flex flex-col justify-center gap-1.5 text-xs sm:order-2">
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Estatura</span>
                                                        <span
                                                            class="font-semibold text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['estatura']) && $circunferencias['estatura'] > 0 ? number_format((float) $circunferencias['estatura'], 1) . ' cm' : '-' }}</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Cuello</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['cuello']) && $circunferencias['cuello'] > 0 ? number_format($circunferencias['cuello'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Brazo
                                                            Normal</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['brazo_normal']) && $circunferencias['brazo_normal'] > 0 ? number_format($circunferencias['brazo_normal'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Brazo
                                                            Contraído</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['brazo_contraido']) && $circunferencias['brazo_contraido'] > 0 ? number_format($circunferencias['brazo_contraido'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Circunferencia
                                                            tórax</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['torax']) && $circunferencias['torax'] > 0 ? number_format($circunferencias['torax'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Cintura</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['cintura']) && $circunferencias['cintura'] > 0 ? number_format($circunferencias['cintura'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Cintura
                                                            baja</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['cintura_baja']) && $circunferencias['cintura_baja'] > 0 ? number_format($circunferencias['cintura_baja'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Cadera</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['cadera']) && $circunferencias['cadera'] > 0 ? number_format($circunferencias['cadera'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Muslo</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['muslo']) && $circunferencias['muslo'] > 0 ? number_format($circunferencias['muslo'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span class="text-zinc-600 dark:text-zinc-400">Glúteos</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['gluteos']) && $circunferencias['gluteos'] > 0 ? number_format($circunferencias['gluteos'], 1) : '0' }}cm</span>
                                                    </div>
                                                    <div class="flex items-center justify-between gap-4">
                                                        <span
                                                            class="text-zinc-600 dark:text-zinc-400">Pantorrilla</span>
                                                        <span
                                                            class="font-medium text-zinc-900 dark:text-zinc-100">{{ isset($circunferencias['pantorrilla']) && $circunferencias['pantorrilla'] > 0 ? number_format($circunferencias['pantorrilla'], 1) : '0' }}cm</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($proximaEvaluacionFecha ?? null)
                                        <p class="flex items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                            <span class="size-1.5 rounded-full bg-green-500"></span>
                                            Próxima evaluación: {{ $proximaEvaluacionFecha }}
                                        </p>
                                    @endif

                                    <!-- Historial de Evaluaciones -->
                                    <div
                                        class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-3">
                                        <div
                                            class="flex items-center gap-2 border-b border-zinc-200 dark:border-zinc-700 pb-2 mb-3">
                                            <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">
                                                HISTORIAL DE EVALUACIONES</h3>
                                        </div>
                                        <div class="flex gap-3 items-center justify-end mb-3">
                                            <select wire:model.live="estadoFilter"
                                                class="w-full max-w-[140px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                <option value="">Todos</option>
                                                <option value="pendiente">Pendiente</option>
                                                <option value="completada">Completada</option>
                                                <option value="cancelada">Cancelada</option>
                                            </select>
                                            <select wire:model.live="perPage"
                                                class="w-full max-w-[100px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                                <option value="10">10</option>
                                                <option value="20">20</option>
                                                <option value="50">50</option>
                                            </select>
                                        </div>
                                        <div
                                            class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-900">
                                            <div class="overflow-x-auto">
                                                <table class="w-full text-sm">
                                                    <thead class="bg-zinc-100 dark:bg-zinc-800">
                                                        <tr>
                                                            <th
                                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                                Fecha</th>
                                                            <th
                                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                                Objetivo</th>
                                                            <th
                                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                                Peso</th>
                                                            <th
                                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                                Nutricionista</th>
                                                            <th
                                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                                Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                                        @forelse ($evaluaciones as $evaluacion)
                                                            <tr>
                                                                <td
                                                                    class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                                                    {{ $evaluacion->created_at->format('d/m/Y') }}</td>
                                                                <td
                                                                    class="px-4 py-2.5 text-zinc-900 dark:text-zinc-100">
                                                                    {{ $evaluacion->objetivo ?? '-' }}</td>
                                                                <td
                                                                    class="px-4 py-2.5 text-zinc-900 dark:text-zinc-100">
                                                                    {{ $evaluacion->peso ? number_format($evaluacion->peso, 2) . ' kg' : '-' }}
                                                                </td>
                                                                <td
                                                                    class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                                                    {{ $evaluacion->nutricionista->name ?? '-' }}</td>
                                                                <td class="px-4 py-2.5">
                                                                    <div class="flex gap-1">
                                                                        @can('gestion-nutricional.update')
                                                                        <flux:button variant="ghost" size="xs"
                                                                            icon="pencil"
                                                                            wire:click="openEditEvaluacionModal({{ $evaluacion->id }})"
                                                                            aria-label="Editar" />
                                                                        @endcan
                                                                        @can('gestion-nutricional.delete')
                                                                        <flux:button variant="ghost" size="xs"
                                                                            icon="trash" color="red"
                                                                            wire:click="openDeleteEvaluacionModal({{ $evaluacion->id }})"
                                                                            aria-label="Eliminar" />
                                                                        @endcan
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="5"
                                                                    class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                                                    No se encontraron evaluaciones</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                        @if ($evaluaciones->hasPages())
                                            <div class="mt-3">{{ $evaluaciones->links() }}</div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div
                                    class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8">
                                    <div class="flex flex-col items-center justify-center text-center">
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No hay evaluaciones
                                            registradas para este cliente</p>
                                        @can('gestion-nutricional.create')
                                        <flux:button icon="plus" color="purple" variant="primary" size="xs"
                                            wire:click="openCreateEvaluacionModal" class="mt-4">Crear Primera
                                            Evaluación</flux:button>
                                        @endcan
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Contenido de Pestaña: NUTRICIÓN -->
                    @if ($mainTab === 'nutricion')
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Seguimiento
                                    Nutricional</h2>
                                @can('gestion-nutricional.create')
                                <flux:button icon="plus" color="purple" variant="primary" size="xs"
                                    wire:click="openCreateNutricionModal">Nuevo seguimiento</flux:button>
                                @endcan
                            </div>
                            <div class="flex gap-3 items-center justify-end">
                                <select wire:model.live="tipoFilter"
                                    class="w-full max-w-[160px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="">Todos los tipos</option>
                                    <option value="plan_inicial">Plan inicial</option>
                                    <option value="seguimiento">Seguimiento</option>
                                    <option value="recomendacion">Recomendación</option>
                                </select>
                                <select wire:model.live="estadoFilter"
                                    class="w-full max-w-[120px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="">Todos</option>
                                    <option value="borrador">Borrador</option>
                                    <option value="activo">Activo</option>
                                    <option value="archivado">Archivado</option>
                                </select>
                                <select wire:model.live="perPage"
                                    class="w-full max-w-[100px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                </select>
                            </div>
                            <div
                                class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <table class="w-full text-sm">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                                        <tr>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Fecha</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Tipo</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Objetivo</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Estado</th>
                                            <th
                                                class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @forelse ($seguimientos as $s)
                                            <tr>
                                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                                    {{ $s->fecha->format('d/m/Y') }}</td>
                                                <td class="px-4 py-2.5 text-zinc-900 dark:text-zinc-100">
                                                    {{ ucfirst(str_replace('_', ' ', $s->tipo)) }}</td>
                                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                                    {{ Str::limit($s->objetivo, 30) ?: '-' }}</td>
                                                <td class="px-4 py-2.5"><span
                                                        class="inline-flex rounded-full px-2 py-0.5 text-xs {{ $s->estado === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ $s->estado }}</span>
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    @can('gestion-nutricional.update')
                                                    <flux:button variant="ghost" size="xs" icon="pencil"
                                                        wire:click="openEditNutricionModal({{ $s->id }})"
                                                        aria-label="Editar" />
                                                    @endcan
                                                    @can('gestion-nutricional.delete')
                                                    <flux:button variant="ghost" size="xs" icon="trash"
                                                        color="red"
                                                        wire:click="openDeleteNutricionModal({{ $s->id }})"
                                                        aria-label="Eliminar" />
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5"
                                                    class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No
                                                    hay seguimientos</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($seguimientos->hasPages())
                                <div class="mt-4 flex justify-end">{{ $seguimientos->links() }}</div>
                            @endif
                        </div>
                    @endif

                    <!-- Contenido de Pestaña: CITAS -->
                    @if ($mainTab === 'citas')
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Reserva de Citas
                                </h2>
                                @can('gestion-nutricional.create')
                                <flux:button icon="plus" color="purple" variant="primary" size="xs"
                                    wire:click="openCreateCitaModal">Nueva cita</flux:button>
                                @endcan
                            </div>
                            <div class="flex gap-3 items-center justify-end">
                                <select wire:model.live="tipoFilter"
                                    class="w-full max-w-[160px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="">Todos</option>
                                    <option value="evaluacion">Evaluación</option>
                                    <option value="consulta_nutricional">Consulta nutricional</option>
                                    <option value="seguimiento">Seguimiento</option>
                                    <option value="otro">Otro</option>
                                </select>
                                <select wire:model.live="estadoFilter"
                                    class="w-full max-w-[140px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="">Todos</option>
                                    <option value="programada">Programada</option>
                                    <option value="confirmada">Confirmada</option>
                                    <option value="completada">Completada</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                                <select wire:model.live="perPage"
                                    class="w-full max-w-[100px] rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="10">10</option>
                                    <option value="15">15</option>
                                    <option value="25">25</option>
                                </select>
                            </div>
                            <div
                                class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <table class="w-full text-sm">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                                        <tr>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Fecha/Hora</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Tipo</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Profesional</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Estado</th>
                                            <th
                                                class="px-4 py-2 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400">
                                                Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @forelse ($citas as $cita)
                                            <tr>
                                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                                    {{ $cita->fecha_hora->format('d/m/Y H:i') }}</td>
                                                <td class="px-4 py-2.5 text-zinc-900 dark:text-zinc-100">
                                                    {{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}</td>
                                                <td class="px-4 py-2.5 text-zinc-600 dark:text-zinc-400">
                                                    @if ($cita->nutricionista)
                                                        {{ $cita->nutricionista->name }}
                                                    @elseif ($cita->trainerUser)
                                                        {{ $cita->trainerUser->name }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="px-4 py-2.5">
                                                    <span
                                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium
                                                        {{ $cita->estado === 'completada' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : '' }}
                                                        {{ $cita->estado === 'cancelada' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : '' }}
                                                        {{ in_array($cita->estado, ['programada', 'confirmada']) ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : '' }}
                                                    ">{{ ucfirst(str_replace('_', ' ', $cita->estado)) }}</span>
                                                </td>
                                                <td class="px-4 py-2.5 text-right">
                                                    @can('gestion-nutricional.update')
                                                    @if (in_array($cita->estado, ['programada', 'confirmada', 'en_curso']))
                                                        <flux:button variant="ghost" size="xs" icon="x-mark"
                                                            color="red"
                                                            wire:click="cancelarCita({{ $cita->id }})"
                                                            aria-label="Cancelar" />
                                                    @endif
                                                    <flux:button variant="ghost" size="xs" icon="pencil"
                                                        wire:click="openEditCitaModal({{ $cita->id }})"
                                                        aria-label="Editar" />
                                                    @endcan
                                                    @can('gestion-nutricional.delete')
                                                    <flux:button variant="ghost" size="xs" icon="trash"
                                                        color="red"
                                                        wire:click="openDeleteCitaModal({{ $cita->id }})"
                                                        aria-label="Eliminar" />
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5"
                                                    class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">No
                                                    hay citas</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            @if ($citas->hasPages())
                                <div class="mt-4 flex justify-end">{{ $citas->links() }}</div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8">
                <div class="flex flex-col items-center justify-center text-center">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">Busca y selecciona un cliente para ver su
                        información</p>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal: Evaluación -->
    <flux:modal name="evaluacion-modal" wire:model="modalState.evaluacion" focusable flyout variant="floating"
        class="md:w-2xl">
        <form wire:submit="saveEvaluacion">
            <div class="space-y-3 p-4 max-h-[80vh] overflow-y-auto">
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                        {{ $evaluacionId ? 'Editar Evaluación' : 'Nueva Evaluación' }}</h2>
                    <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        {{ $evaluacionId ? 'Modifica la información' : 'Registra una nueva evaluación de medidas' }}
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model.live.number="evaluacionFormData.peso" label="Peso (kg)"
                        type="number" step="0.01" min="0" />
                    <flux:input size="xs" wire:model.live.number="evaluacionFormData.estatura"
                        label="Estatura (m)" type="number" step="0.01" min="0.5" max="3" />
                </div>
                <div>
                    <flux:input size="xs" wire:model.number="evaluacionFormData.imc" label="IMC"
                        type="number" step="0.01" readonly />
                </div>
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Composición Corporal</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <flux:input size="xs" wire:model.number="evaluacionFormData.porcentaje_grasa"
                            label="% Grasa" type="number" step="0.01" min="0" max="100" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_grasa"
                            label="Masa Grasa (kg)" type="number" step="0.01" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.porcentaje_musculo"
                            label="% Músculo" type="number" step="0.01" min="0" max="100" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_muscular"
                            label="Masa Muscular (kg)" type="number" step="0.01" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_osea"
                            label="Masa Ósea (kg)" type="number" step="0.01" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.masa_residual"
                            label="Masa Residual (kg)" type="number" step="0.01" min="0" />
                    </div>
                </div>
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <h3 class="mb-2 text-xs font-semibold text-zinc-900 dark:text-zinc-100">Circunferencias (cm)</h3>
                    <div class="grid grid-cols-3 gap-2">
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.estatura"
                            label="Estatura" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cuello"
                            label="Cuello" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.brazo_normal"
                            label="Brazo Normal" type="number" step="0.1" min="0" />
                        <flux:input size="xs"
                            wire:model.number="evaluacionFormData.circunferencias.brazo_contraido"
                            label="Brazo Contraído" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.torax"
                            label="Tórax" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cintura"
                            label="Cintura" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cintura_baja"
                            label="Cintura Baja" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.cadera"
                            label="Cadera" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.muslo"
                            label="Muslo" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.gluteos"
                            label="Glúteos" type="number" step="0.1" min="0" />
                        <flux:input size="xs" wire:model.number="evaluacionFormData.circunferencias.pantorrilla"
                            label="Pantorrilla" type="number" step="0.1" min="0" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <flux:input size="xs" wire:model="evaluacionFormData.presion_arterial"
                        label="Presión Arterial" placeholder="Ej: 120/80" />
                    <flux:input size="xs" wire:model.number="evaluacionFormData.frecuencia_cardiaca"
                        label="Frecuencia Cardíaca" type="number" min="0" max="300" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Objetivo</label>
                    <select wire:model="evaluacionFormData.objetivo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="DEPORTES Ó SALUD">DEPORTES Ó SALUD</option>
                        <option value="PÉRDIDA DE PESO">PÉRDIDA DE PESO</option>
                        <option value="GANANCIA DE MASA">GANANCIA DE MASA</option>
                        <option value="TONIFICACIÓN">TONIFICACIÓN</option>
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Nutricionista</label>
                    <select wire:model="evaluacionFormData.nutricionista_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Sin nutricionista</option>
                        @foreach ($nutricionistas as $nutricionista)
                            <option value="{{ $nutricionista->id }}">{{ $nutricionista->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label
                        class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">Observaciones</label>
                    <textarea wire:model="evaluacionFormData.observaciones" rows="3"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                </div>
            </div>
            <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
                <flux:modal.close>
                    <flux:button variant="ghost" size="xs" wire:click="closeEvaluacionModal" type="button">
                        Cancelar</flux:button>
                </flux:modal.close>
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled"
                    wire:target="saveEvaluacion">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="saveEvaluacion" />
                        <span wire:loading.remove wire:target="saveEvaluacion">{{ $evaluacionId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="saveEvaluacion">Guardando...</span>
                    </span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="delete-evaluacion-modal" wire:model="modalState.delete_evaluacion" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Eliminar Evaluación</h2>
            <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">¿Estás seguro de que deseas eliminar esta
                evaluación? Esta acción no se puede deshacer.</p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" size="xs" wire:click="closeEvaluacionModal" type="button">
                    Cancelar</flux:button>
            </flux:modal.close>
            <flux:button variant="danger" size="xs" wire:click="deleteEvaluacion" type="button"
                wire:loading.attr="disabled" wire:target="deleteEvaluacion">
                <span class="inline-flex items-center gap-1.5">
                    <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="deleteEvaluacion" />
                    <span wire:loading.remove wire:target="deleteEvaluacion">Eliminar</span>
                    <span wire:loading wire:target="deleteEvaluacion">Eliminando...</span>
                </span>
            </flux:button>
        </div>
    </flux:modal>

    <!-- Modal: Nutrición -->
    <flux:modal name="nutricion-form" wire:model="modalState.nutricion" focusable flyout variant="floating"
        class="md:w-lg">
        <form wire:submit="saveNutricion">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $seguimientoId ? 'Editar seguimiento' : 'Nuevo seguimiento' }}</h2>
                <flux:field>
                    <flux:label>Tipo</flux:label>
                    <select wire:model="nutricionFormData.tipo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="plan_inicial">Plan inicial</option>
                        <option value="seguimiento">Seguimiento</option>
                        <option value="recomendacion">Recomendación</option>
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Fecha</flux:label>
                    <flux:input type="date" wire:model="nutricionFormData.fecha" />
                </flux:field>
                <flux:field>
                    <flux:label>Objetivo</flux:label>
                    <flux:input wire:model="nutricionFormData.objetivo" placeholder="Objetivo del plan" />
                </flux:field>
                <flux:field>
                    <flux:label>Calorías objetivo</flux:label>
                    <flux:input type="number" wire:model="nutricionFormData.calorias_objetivo" min="0"
                        placeholder="Opcional" />
                </flux:field>
                <flux:field>
                    <flux:label>Contenido / Recomendaciones</flux:label>
                    <textarea wire:model="nutricionFormData.contenido" rows="4"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                </flux:field>
                <flux:field>
                    <flux:label>Nutricionista</flux:label>
                    <select wire:model="nutricionFormData.nutricionista_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Sin asignar</option>
                        @foreach ($nutricionistas as $n)
                            <option value="{{ $n->id }}">{{ $n->name }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Vincular a cita (opcional)</flux:label>
                    <select wire:model="nutricionFormData.cita_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Ninguna</option>
                        @foreach ($citasCliente as $c)
                            <option value="{{ $c->id }}">{{ $c->fecha_hora->format('d/m/Y H:i') }} -
                                {{ $c->tipo }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="nutricionFormData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="borrador">Borrador</option>
                        <option value="activo">Activo</option>
                        <option value="archivado">Archivado</option>
                    </select>
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="$set('modalState.nutricion', false)">
                        Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="nutricion-delete" wire:model="modalState.delete_nutricion" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Eliminar seguimiento</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">¿Estás seguro? Esta acción no se puede deshacer.
            </p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('modalState.delete_nutricion', false)">Cancelar
            </flux:button>
            <flux:button color="red" variant="primary" wire:click="deleteNutricion">Eliminar</flux:button>
        </div>
    </flux:modal>

    <!-- Modal: Cita -->
    <flux:modal name="cita-form" wire:model="modalState.cita" focusable flyout variant="floating" class="md:w-lg">
        <form wire:submit="saveCita">
            <div class="space-y-3 p-4">
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $citaId ? 'Editar cita' : 'Nueva cita' }}</h2>
                <flux:field>
                    <flux:label>Tipo</flux:label>
                    <select wire:model="citaFormData.tipo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="evaluacion">Evaluación</option>
                        <option value="consulta_nutricional">Consulta nutricional</option>
                        <option value="seguimiento">Seguimiento</option>
                        <option value="otro">Otro</option>
                    </select>
                </flux:field>
                <div class="grid grid-cols-2 gap-2">
                    <flux:field>
                        <flux:label>Fecha y hora</flux:label>
                        <flux:input type="datetime-local" wire:model="citaFormData.fecha_hora" required />
                    </flux:field>
                    <flux:field>
                        <flux:label>Duración (min)</flux:label>
                        <flux:input type="number" wire:model="citaFormData.duracion_minutos" min="15"
                            max="480" />
                    </flux:field>
                </div>
                <flux:field>
                    <flux:label>Nutricionista</flux:label>
                    <select wire:model="citaFormData.nutricionista_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Sin asignar</option>
                        @foreach ($nutricionistas as $n)
                            <option value="{{ $n->id }}">{{ $n->name }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Trainer</flux:label>
                    <select wire:model="citaFormData.trainer_user_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Sin asignar</option>
                        @foreach ($trainers as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Estado</flux:label>
                    <select wire:model="citaFormData.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="programada">Programada</option>
                        <option value="confirmada">Confirmada</option>
                        <option value="en_curso">En curso</option>
                        <option value="completada">Completada</option>
                        <option value="cancelada">Cancelada</option>
                        <option value="no_asistio">No asistió</option>
                    </select>
                </flux:field>
                <flux:field>
                    <flux:label>Observaciones</flux:label>
                    <textarea wire:model="citaFormData.observaciones" rows="2"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                </flux:field>
                <div class="flex justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="$set('modalState.cita', false)">Cancelar
                    </flux:button>
                    <flux:button type="submit" variant="primary">Guardar</flux:button>
                </div>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="cita-delete" wire:model="modalState.delete_cita" focusable class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Eliminar cita</h2>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">¿Estás seguro? Esta acción no se puede deshacer.
            </p>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:button variant="ghost" wire:click="$set('modalState.delete_cita', false)">Cancelar</flux:button>
            <flux:button color="red" variant="primary" wire:click="deleteCita">Eliminar</flux:button>
        </div>
    </flux:modal>

    {{-- Modal de previsualización del reporte (imprimir o descargar) --}}
    <flux:modal name="reporte-preview-modal" wire:model="modalState.reporte_preview" focusable class="md:max-w-4xl"
        variant="floating">
        <flux:heading>Reporte de Evaluación</flux:heading>
        <flux:subheading>Previsualiza el PDF y luego imprime o descarga.</flux:subheading>
        @if ($evaluacionIdReporte)
            <div class="mt-4 flex flex-col gap-3">
                <div
                    class="min-h-[400px] w-full overflow-hidden rounded-lg border border-zinc-200 bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800">
                    <iframe src="{{ route('reportes.evaluacion.preview', $evaluacionIdReporte) }}"
                        class="h-[70vh] w-full min-h-[400px] border-0" title="Vista previa del reporte"></iframe>
                </div>
                <div
                    class="flex flex-wrap items-center justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                    <flux:button variant="ghost" wire:click="cerrarPreviewReporte">
                        Cerrar
                    </flux:button>
                    <a href="{{ $evaluacionIdReporte ? route('reportes.evaluacion.preview', $evaluacionIdReporte) : '#' }}"
                        target="_blank" rel="noopener"
                        class="inline-flex items-center justify-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900 dark:hover:bg-zinc-200">
                        Imprimir (abre en nueva pestaña)
                    </a>
                    <a href="{{ $evaluacionIdReporte ? route('reportes.evaluacion.descargar', $evaluacionIdReporte) : '#' }}"
                        target="_blank" download
                        class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-4 py-2 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700">
                        Descargar PDF
                    </a>
                </div>
            </div>
        @endif
    </flux:modal>

    @can('gestion-nutricional.update')
    <flux:modal name="salud-modal" wire:model="modalState.salud" focusable flyout variant="floating" class="md:w-lg">
        <div class="p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 mb-3">Salud / Nutrición</h2>
            @if($saludClienteId)
                <livewire:nutrition.health-record-form :cliente-id="$saludClienteId" :key="'salud-'.$saludClienteId" />
            @endif
        </div>
    </flux:modal>
    @endcan
</div>

<script>
    function composicionChartData(data, pesoTotal) {
        return {
            chart: null,
            initChart(canvas) {
                if (typeof Chart === 'undefined') {
                    console.warn('Chart.js no está cargado');
                    return;
                }

                const ctx = canvas.getContext('2d');
                const composicion = data;

                const chartData = [];
                const labels = [];
                const colors = ['#3b82f6', '#ef4444', '#8b5cf6', '#f59e0b'];

                if (composicion.masa_muscular && composicion.masa_muscular.kg > 0) {
                    chartData.push(composicion.masa_muscular.kg);
                    labels.push('Masa Muscular');
                }

                if (composicion.masa_grasa && composicion.masa_grasa.kg > 0) {
                    chartData.push(composicion.masa_grasa.kg);
                    labels.push('Masa Grasa');
                }

                if (composicion.masa_osea && composicion.masa_osea.kg > 0) {
                    chartData.push(composicion.masa_osea.kg);
                    labels.push('Masa Ósea');
                }

                if (composicion.masa_residual && composicion.masa_residual.kg > 0) {
                    chartData.push(composicion.masa_residual.kg);
                    labels.push('Masa Residual');
                }

                if (chartData.length > 0) {
                    this.chart = new Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Composición Corporal',
                                data: chartData,
                                backgroundColor: colors.slice(0, chartData.length),
                                borderWidth: 2,
                                borderColor: '#ffffff',
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        font: {
                                            size: 10
                                        },
                                        padding: 8,
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: (context) => {
                                            const label = context.label || '';
                                            const value = context.parsed || 0;
                                            const percentage = pesoTotal > 0 ? ((value / pesoTotal) * 100)
                                                .toFixed(1) : 0;
                                            return label + ': ' + value.toFixed(2) + ' kg (' + percentage +
                                                '%)';
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }
        }
    }
</script>
