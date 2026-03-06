<div class="space-y-3 border border-zinc-200 rounded-lg p-3 dark:border-zinc-700" wire:key="calendario-wrap">
    <div class="flex flex-col gap-3">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Calendario de Citas</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Visualiza y gestiona las citas en el calendario</p>
        </div>

        {{-- Leyenda de estados --}}
        <div class="flex flex-wrap items-center gap-x-4 gap-y-2 rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/50">
            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Estados:</span>
            @foreach ([
                ['estado' => 'programada', 'label' => 'Programada', 'color' => '#3b82f6'],
                ['estado' => 'confirmada', 'label' => 'Confirmada', 'color' => '#22c55e'],
                ['estado' => 'en_curso', 'label' => 'En curso', 'color' => '#f59e0b'],
                ['estado' => 'completada', 'label' => 'Completada', 'color' => '#64748b'],
                ['estado' => 'cancelada', 'label' => 'Cancelada', 'color' => '#ef4444'],
                ['estado' => 'no_asistio', 'label' => 'No asistió', 'color' => '#f97316'],
            ] as $item)
                <div class="flex items-center gap-1.5">
                    <span class="h-3 w-3 shrink-0 rounded-full" style="background-color: {{ $item['color'] }}; border: 1px solid {{ $item['color'] }};"></span>
                    <span class="text-xs text-zinc-700 dark:text-zinc-300">{{ $item['label'] }}</span>
                </div>
            @endforeach
        </div>

        <div class="min-w-0 overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <div id="calendar" class="min-h-[360px] max-h-[520px] w-full" wire:ignore></div>
        </div>
    </div>

    {{-- Modal detalle cita + cliente (Flux UI) --}}
    <flux:modal name="detalle-cita-modal" wire:model="modalDetalle" focusable flyout variant="floating" class="md:w-xl">
        @if ($cita)
            <div class="p-4 space-y-4">
                <div class="flex items-center justify-between border-b border-zinc-200 dark:border-zinc-700 pb-3">
                    <flux:heading size="lg">Detalle de la cita</flux:heading>
                    <flux:button variant="ghost" size="xs" icon="x-mark" wire:click="cerrarDetalleCita" aria-label="Cerrar" />
                </div>

                {{-- Datos de la cita --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-2">
                    <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Cita</h3>
                    <dl class="grid grid-cols-1 gap-1.5 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Fecha y hora</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->fecha_hora->format('d/m/Y H:i') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Tipo</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst(str_replace('_', ' ', $cita->tipo)) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Duración</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->duracion_minutos ?? 60 }} min</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-zinc-500 dark:text-zinc-400">Profesional</dt>
                            <dd class="font-medium text-zinc-900 dark:text-zinc-100">
                                @if ($cita->nutricionista) {{ $cita->nutricionista->name }}
                                @elseif ($cita->trainerUser) {{ $cita->trainerUser->name }}
                                @else —
                                @endif
                            </dd>
                        </div>
                        @if ($cita->observaciones)
                            <div class="pt-1 border-t border-zinc-100 dark:border-zinc-700">
                                <dt class="text-zinc-500 dark:text-zinc-400 mb-0.5">Observaciones</dt>
                                <dd class="text-zinc-700 dark:text-zinc-300 text-xs">{{ $cita->observaciones }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- Datos del cliente --}}
                @if ($cita->cliente)
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-2">
                        <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Cliente</h3>
                        <dl class="grid grid-cols-1 gap-1.5 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-zinc-500 dark:text-zinc-400">Nombre</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->cliente->nombres }} {{ $cita->cliente->apellidos }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-zinc-500 dark:text-zinc-400">Documento</dt>
                                <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->cliente->tipo_documento }} {{ $cita->cliente->numero_documento }}</dd>
                            </div>
                            @if ($cita->cliente->telefono)
                                <div class="flex justify-between">
                                    <dt class="text-zinc-500 dark:text-zinc-400">Teléfono</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cita->cliente->telefono }}</dd>
                                </div>
                            @endif
                            @if ($cita->cliente->email)
                                <div class="flex justify-between">
                                    <dt class="text-zinc-500 dark:text-zinc-400">Email</dt>
                                    <dd class="font-medium text-zinc-900 dark:text-zinc-100 truncate max-w-[180px]" title="{{ $cita->cliente->email }}">{{ $cita->cliente->email }}</dd>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <dt class="text-zinc-500 dark:text-zinc-400">Estado cliente</dt>
                                <dd>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $cita->cliente->estado_cliente === 'activo' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400' }}">{{ $cita->cliente->estado_cliente ?? 'activo' }}</span>
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endif

                {{-- Cambiar estado --}}
                <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-3 space-y-2">
                    <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Cambiar estado</h3>
                    <form wire:submit="actualizarEstadoCita" class="flex flex-wrap items-end gap-2">
                        <flux:field class="flex-1 min-w-[160px]">
                            <flux:label>Estado</flux:label>
                            <select wire:model="estadoCita"
                                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                <option value="programada">Programada</option>
                                <option value="confirmada">Confirmada</option>
                                <option value="en_curso">En curso</option>
                                <option value="completada">Completada</option>
                                <option value="cancelada">Cancelada</option>
                                <option value="no_asistio">No asistió</option>
                            </select>
                        </flux:field>
                        <flux:button type="submit" variant="primary" size="sm">Guardar estado</flux:button>
                    </form>
                    <flux:error name="estadoCita" />
                </div>
            </div>
        @endif
    </flux:modal>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js"></script>
@endpush

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css" rel="stylesheet">
<style>
    /* Evitar desborde: contenedor del calendario */
    .min-w-0.overflow-hidden {
        min-width: 0;
    }
    .min-w-0.overflow-hidden .fc {
        max-width: 100%;
        overflow: hidden;
    }
    .min-w-0.overflow-hidden .fc-view-harness {
        max-width: 100%;
        overflow: hidden;
    }
    .min-w-0.overflow-hidden #calendar {
        overflow: hidden;
        max-width: 100%;
        box-sizing: border-box;
    }

    /* Contenedor del calendario: integración visual con la app */
    #calendar {
        --fc-border-color: rgb(228 228 231);
        --fc-button-bg-color: rgb(244 244 245);
        --fc-button-border-color: rgb(228 228 231);
        --fc-button-hover-bg-color: rgb(228 228 231);
        --fc-button-hover-border-color: rgb(212 212 216);
        --fc-button-active-bg-color: rgb(228 228 231);
        --fc-today-bg-color: rgb(244 250 255);
        --fc-page-bg-color: #fff;
        --fc-neutral-bg-color: rgb(250 250 250);
        --fc-list-event-hover-bg-color: rgb(244 244 245);
    }
    .dark #calendar,
    .dark #calendar.fc {
        --fc-border-color: rgb(63 63 70);
        --fc-button-bg-color: rgb(39 39 42);
        --fc-button-border-color: rgb(63 63 70);
        --fc-button-hover-bg-color: rgb(63 63 70);
        --fc-button-hover-border-color: rgb(82 82 91);
        --fc-button-active-bg-color: rgb(63 63 70);
        --fc-today-bg-color: rgb(39 39 42);
        --fc-page-bg-color: rgb(24 24 27);
        --fc-neutral-bg-color: rgb(39 39 42);
        --fc-list-event-hover-bg-color: rgb(63 63 70);
    }
    #calendar .fc {
        font-family: inherit;
    }
    #calendar .fc-toolbar-title {
        font-size: 1.25rem;
        font-weight: 600;
    }
    #calendar .fc-button {
        padding: 0.4em 0.65em;
        font-size: 0.875rem;
        text-transform: capitalize;
        border-radius: 0.5rem;
        font-weight: 500;
    }
    #calendar .fc-button-primary:not(:disabled):active,
    #calendar .fc-button-primary:not(:disabled).fc-button-active {
        background: var(--fc-button-active-bg-color);
        border-color: var(--fc-button-border-color);
    }
    #calendar .fc-col-header-cell-cushion {
        padding: 0.5rem 0.25rem;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.025em;
    }
    #calendar .fc-daygrid-day-number {
        font-size: 0.8125rem;
        padding: 0.25rem 0.35rem;
        border-radius: 0.375rem;
    }
    #calendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        background: rgb(59 130 246 / 0.2);
        color: rgb(37 99 235);
        font-weight: 600;
    }
    .dark #calendar .fc-daygrid-day.fc-day-today .fc-daygrid-day-number {
        background: rgb(59 130 246 / 0.25);
        color: rgb(147 197 253);
    }
    /* Eventos: bordes redondeados y texto legible */
    #calendar .fc-event {
        border-radius: 0.375rem;
        padding: 0.15rem 0.35rem;
        font-size: 0.75rem;
        font-weight: 500;
        border: none;
        cursor: pointer;
    }
    #calendar .fc-event:hover {
        filter: brightness(1.05);
    }
    #calendar .fc-event .fc-event-main {
        color: #fff;
        text-shadow: 0 0 1px rgba(0,0,0,0.2);
    }
    #calendar .fc-event .fc-event-time {
        font-weight: 600;
    }
    /* Vista lista */
    #calendar .fc-list-event:hover td {
        background: var(--fc-list-event-hover-bg-color);
    }
    #calendar .fc-list-event-dot {
        border-radius: 50%;
        border-width: 2px;
    }
    #calendar .fc-list-day-cushion {
        padding: 0.5rem 0.75rem;
        font-weight: 600;
        font-size: 0.8125rem;
    }
    /* Time grid: franjas horarias */
    #calendar .fc-timegrid-slot {
        height: 2.5rem;
    }
    #calendar .fc-timegrid-slot-label {
        font-size: 0.75rem;
        vertical-align: middle;
    }
</style>
@endpush

<script>
(function() {
    var eventosUrl = @json(route('gestion-nutricional.calendario.eventos'));
    var calendarInstance = null;

    function getLivewireComponent() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return null;
        var root = calendarEl.closest('[wire\\:id]');
        return root && window.Livewire ? window.Livewire.find(root.getAttribute('wire:id')) : null;
    }

    function initCalendar() {
        var calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;
        if (typeof FullCalendar === 'undefined') {
            setTimeout(initCalendar, 50);
            return;
        }
        if (calendarInstance) { calendarInstance.destroy(); calendarInstance = null; }
        calendarInstance = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            locale: 'es',
            firstDay: 1,
            height: 'auto',
            contentHeight: 340,
            headerToolbar: { left: 'prev,next today', center: 'title', right: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek,listMonth' },
            buttonText: {
                today: 'Hoy',
                month: 'Mes',
                week: 'Semana',
                day: 'Día',
                listMonth: 'Lista mes',
                listWeek: 'Lista semana'
            },
            titleFormat: { year: 'numeric', month: 'long' },
            dayHeaderFormat: { weekday: 'short' },
            views: {
                dayGridMonth: { dayMaxEvents: 4, buttonText: 'Mes' },
                timeGridWeek: { buttonText: 'Semana' },
                timeGridDay: { buttonText: 'Día' },
                listWeek: { buttonText: 'Lista semana' },
                listMonth: { buttonText: 'Lista mes' }
            },
            slotMinTime: '05:00:00',
            slotMaxTime: '22:00:00',
            slotLabelFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
            displayEventTime: true,
            displayEventEnd: true,
            eventDisplay: 'block',
            events: function(info, successCallback, failureCallback) {
                fetch(eventosUrl + '?start=' + encodeURIComponent(info.startStr) + '&end=' + encodeURIComponent(info.endStr), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                }).then(function(r) { return r.json(); }).then(successCallback).catch(failureCallback);
            },
            eventDidMount: function(info) {
                var estado = info.event.extendedProps && info.event.extendedProps.estado ? info.event.extendedProps.estado : '';
                var tipo = info.event.extendedProps && info.event.extendedProps.tipo ? info.event.extendedProps.tipo : '';
                var label = estado ? (estado + (tipo ? ' · ' + tipo : '')) : info.event.title;
                info.el.setAttribute('title', label);
            },
            eventClick: function(info) {
                info.jsEvent.preventDefault();
                var id = info.event.id;
                if (id) {
                    var lw = getLivewireComponent();
                    if (lw && lw.call) lw.call('abrirDetalleCita', parseInt(id));
                }
            }
        });
        calendarInstance.render();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initCalendar); else initCalendar();
    document.addEventListener('livewire:navigated', function() { setTimeout(initCalendar, 100); });
    document.addEventListener('calendario-refrescar', function() {
        if (calendarInstance) calendarInstance.refetchEvents();
    });
})();
</script>
