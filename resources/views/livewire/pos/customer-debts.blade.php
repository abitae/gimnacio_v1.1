<div class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
    <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
        <div>
            <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Cuentas por cobrar</h1>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">Deudas pendientes con búsqueda ampliada, filtros por fecha y cobro por cliente o por deuda.</p>
        </div>
        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
            <flux:input icon="magnifying-glass" type="search" size="xs" wire:model.live.debounce.300ms="search" placeholder="DNI, código, celular o nombre" />
            <flux:input type="date" size="xs" wire:model.live="fechaInicio" />
            <flux:input type="date" size="xs" wire:model.live="fechaFin" />
            <flux:select size="xs" wire:model.live="estadoFilter">
                <option value="">Todos los estados</option>
                <option value="pendiente">Pendiente</option>
                <option value="parcial">Parcial</option>
                <option value="vencido">Vencido</option>
            </flux:select>
            <flux:select size="xs" wire:model.live="perPage">
                <option value="10">10</option>
                <option value="15">15</option>
                <option value="25">25</option>
                <option value="50">50</option>
            </flux:select>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Código</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Cliente</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Documento</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Celular</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Fecha</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Total</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Saldo</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Estado</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-zinc-600 dark:text-zinc-300">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @forelse ($debts as $d)
                    <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                        <td class="px-4 py-3 text-xs font-semibold text-zinc-800 dark:text-zinc-200">{{ $d->cliente?->codigo ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $d->cliente ? $d->cliente->nombres.' '.$d->cliente->apellidos : '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $d->cliente?->tipo_documento }} {{ $d->cliente?->numero_documento }}</td>
                        <td class="px-4 py-3 text-xs">{{ $d->cliente?->telefono ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs">{{ $d->fecha_registro?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-xs">S/ {{ number_format((float) $d->monto_total, 2) }}</td>
                        <td class="px-4 py-3 text-right text-xs font-semibold text-amber-600 dark:text-amber-400">S/ {{ number_format((float) $d->saldo_pendiente, 2) }}</td>
                        <td class="px-4 py-3 text-xs">
                            <span class="rounded-full px-2 py-1 {{ $d->estado === 'vencido' ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300' : ($d->estado === 'parcial' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300' : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300') }}">
                                {{ ucfirst($d->estado) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-1">
                                @if ($d->cliente)
                                    <flux:button size="xs" variant="ghost" href="{{ route('clientes.perfil', $d->cliente) }}" wire:navigate>Ficha</flux:button>
                                    <flux:button size="xs" variant="ghost" wire:click="abrirModalPagoCliente({{ $d->cliente_id }})">Pagar cliente</flux:button>
                                @endif
                                <flux:button size="xs" variant="primary" color="green" wire:click="abrirModalCobro({{ $d->id }})">Pagar deuda</flux:button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-4 py-10 text-center text-xs text-zinc-500 dark:text-zinc-400">No hay deudas pendientes para los filtros seleccionados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">{{ $debts->links() }}</div>

    <flux:modal wire:model="mostrarModalCobro" class="md:w-lg">
        <div class="space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Registrar cobro de deuda</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">Este pago actualizará la cuenta por cobrar y la caja abierta.</p>
            </div>
            <flux:input size="xs" wire:model.live.number="cobroForm.monto_pago" label="Monto a pagar (S/)" type="number" step="0.01" min="0.01" required />
            <flux:select size="xs" wire:model.live="cobroForm.payment_method_id" label="Método de pago">
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->nombre }}</option>
                @endforeach
            </flux:select>
            @if ($selectedPaymentMethod?->requiere_numero_operacion)
                <flux:input size="xs" wire:model.live="cobroForm.numero_operacion" label="Número de operación" />
            @endif
            @if ($selectedPaymentMethod?->requiere_entidad)
                <flux:input size="xs" wire:model.live="cobroForm.entidad_financiera" label="Entidad financiera" />
            @endif
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cerrarModalCobro">Cancelar</flux:button>
                <flux:button variant="primary" color="green" wire:click="procesarCobro">Registrar cobro</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalPagoCliente" class="md:w-lg">
        <div class="space-y-4 p-6">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Pagar deuda total del cliente</h2>
                <p class="text-xs text-zinc-600 dark:text-zinc-400">
                    {{ $clienteSeleccionado ? $clienteSeleccionado->nombres.' '.$clienteSeleccionado->apellidos : 'Cliente' }}
                    · Total pendiente S/ {{ number_format((float) $totalClienteSeleccionado, 2) }}
                </p>
            </div>
            <flux:select size="xs" wire:model.live="cobroForm.payment_method_id" label="Método de pago">
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->nombre }}</option>
                @endforeach
            </flux:select>
            @if ($selectedPaymentMethod?->requiere_numero_operacion)
                <flux:input size="xs" wire:model.live="cobroForm.numero_operacion" label="Número de operación" />
            @endif
            @if ($selectedPaymentMethod?->requiere_entidad)
                <flux:input size="xs" wire:model.live="cobroForm.entidad_financiera" label="Entidad financiera" />
            @endif
            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="cerrarModalPagoCliente">Cancelar</flux:button>
                <flux:button variant="primary" color="green" wire:click="procesarPagoCliente">Pagar total</flux:button>
            </div>
        </div>
    </flux:modal>

    <flux:modal wire:model="mostrarModalTicketPago" class="md:w-5xl">
        <div class="p-4">
            @if ($pagoIdTicket)
                <iframe
                    src="{{ route('pagos.ticket.pdf', ['pago' => $pagoIdTicket]) }}"
                    class="h-[70vh] w-full rounded-xl border border-zinc-200 dark:border-zinc-700"
                    title="Ticket de pago"
                ></iframe>
            @endif
        </div>
    </flux:modal>
</div>
