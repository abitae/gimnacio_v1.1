@php
    use Illuminate\Support\Facades\Storage;
@endphp

{{-- Alta / edición cliente --}}
@canany(['clientes.create', 'clientes.update'])
<flux:modal name="cliente-crud-modal" wire:model="clienteModalState.create" focusable flyout variant="floating" class="md:max-w-xl">
    <form wire:submit.prevent="saveCliente" class="flex max-h-[85vh] flex-col">
        <div class="space-y-3 overflow-y-auto p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                {{ $clienteId ? __('Editar cliente') : __('Nuevo cliente') }}
            </h2>
            <div class="grid gap-2 sm:grid-cols-2">
                <flux:input size="xs" wire:model="formData.tipo_documento" label="{{ __('Tipo doc.') }}" />
                <flux:input size="xs" wire:model="formData.numero_documento" label="{{ __('Número') }}" required />
                <flux:input size="xs" wire:model="formData.nombres" label="{{ __('Nombres') }}" required class="sm:col-span-2" />
                <flux:input size="xs" wire:model="formData.apellidos" label="{{ __('Apellidos') }}" required class="sm:col-span-2" />
                <flux:input size="xs" wire:model="formData.telefono" label="{{ __('Teléfono') }}" />
                <flux:input size="xs" type="email" wire:model="formData.email" label="{{ __('Email') }}" />
                <flux:input size="xs" wire:model="formData.direccion" label="{{ __('Dirección') }}" class="sm:col-span-2" />
                <flux:input size="xs" type="date" wire:model="formData.fecha_nacimiento" label="{{ __('Nacimiento') }}" />
                <flux:input size="xs" wire:model="formData.sexo" label="{{ __('Sexo') }}" />
                <flux:input size="xs" wire:model="formData.ocupacion" label="{{ __('Ocupación') }}" />
                <flux:input size="xs" wire:model="formData.lugar_nacimiento" label="{{ __('Lugar nac.') }}" />
                <flux:input size="xs" wire:model="formData.estado_civil" label="{{ __('Estado civil') }}" />
                <flux:input size="xs" type="number" min="0" wire:model="formData.numero_hijos" label="{{ __('Hijos') }}" />
                <flux:input size="xs" wire:model="formData.placa_carro" label="{{ __('Placa') }}" />
            </div>
            <div class="rounded-lg border border-zinc-200 p-2 dark:border-zinc-700">
                <p class="mb-2 text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Emergencia') }}</p>
                <div class="grid gap-2 sm:grid-cols-3">
                    <flux:input size="xs" wire:model="formData.datos_emergencia.nombre" label="{{ __('Contacto') }}" />
                    <flux:input size="xs" wire:model="formData.datos_emergencia.telefono" label="{{ __('Tel.') }}" />
                    <flux:input size="xs" wire:model="formData.datos_emergencia.relacion" label="{{ __('Relación') }}" />
                </div>
            </div>
            <div class="flex flex-wrap gap-4 text-xs">
                <flux:checkbox wire:model="formData.consentimientos.uso_imagen" label="{{ __('Uso de imagen') }}" />
                <flux:checkbox wire:model="formData.consentimientos.tratamiento_datos" label="{{ __('Tratamiento de datos') }}" />
            </div>
            @if ($clienteId)
                <div class="flex flex-wrap gap-4 text-xs">
                    <flux:checkbox wire:model="formData.biotime_state" label="{{ __('BioTime activo') }}" />
                    <flux:checkbox wire:model="formData.biotime_update" label="{{ __('Actualizar en BioTime') }}" />
                </div>
            @endif
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeClienteCreateModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs" wire:loading.attr="disabled" wire:target="saveCliente">{{ __('Guardar') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcanany

@can('clientes.delete')
<flux:modal name="cliente-delete-modal" wire:model="clienteModalState.delete" focusable class="md:w-lg">
    <div class="p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Eliminar cliente') }}</h2>
        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ __('¿Seguro? Esta acción puede fallar si hay matrículas u otros vínculos.') }}</p>
    </div>
    <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
        <flux:button type="button" variant="ghost" size="xs" wire:click="closeClienteDeleteModal">{{ __('Cancelar') }}</flux:button>
        <flux:button type="button" variant="danger" size="xs" wire:click="deleteCliente">{{ __('Eliminar') }}</flux:button>
    </div>
</flux:modal>
@endcan

@can('clientes.update')
<flux:modal name="cliente-photo-modal" wire:model="clienteModalState.photo" focusable class="md:w-lg">
    <div class="space-y-3 p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Foto del cliente') }}</h2>
        @if ($currentPhoto)
            <img src="{{ Storage::url($currentPhoto) }}" alt="" class="mx-auto max-h-40 rounded-lg object-contain" />
        @endif
        <flux:input type="file" size="xs" wire:model="foto" label="{{ __('Subir imagen (JPEG, PNG, WEBP)') }}" />
        <flux:button type="button" size="xs" variant="primary" wire:click="uploadClientePhoto" wire:loading.attr="disabled" wire:target="uploadClientePhoto,foto">
            {{ __('Guardar foto') }}
        </flux:button>
    </div>
    <div class="flex justify-end border-t border-zinc-200 p-3 dark:border-zinc-700">
        <flux:button type="button" variant="ghost" size="xs" wire:click="closeClientePhotoModal">{{ __('Cerrar') }}</flux:button>
    </div>
</flux:modal>
@endcan

@can('gestion-nutricional.update')
<flux:modal name="cliente-salud-modal" wire:model="clienteModalState.salud" focusable flyout variant="floating" class="md:max-w-2xl">
    <div class="max-h-[90vh] overflow-y-auto p-2">
        @if ($saludClienteId)
            <livewire:nutrition.health-record-form :cliente-id="$saludClienteId" :key="'salud-perfil-'.$saludClienteId" />
        @endif
    </div>
    <div class="flex justify-end border-t border-zinc-200 p-2 dark:border-zinc-700">
        <flux:button type="button" variant="ghost" size="xs" wire:click="closeSaludModal">{{ __('Cerrar') }}</flux:button>
    </div>
</flux:modal>
@endcan

@can('cliente-matriculas.update')
<flux:modal name="cobro-matricula-modal" wire:model="cobroModalAbierto" focusable class="md:w-lg">
    <form wire:submit.prevent="guardarCobroMatricula" class="space-y-3 p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Registrar pago (contado / sin plan de cuotas)') }}</h2>
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Matrícula') }}</label>
            <select wire:model.live="cobroForm.cliente_matricula_id"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800" required>
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($matriculaOpcionesCobro as $op)
                    <option value="{{ $op->id }}">{{ $op->nombre }} · {{ ucfirst($op->estado) }}</option>
                @endforeach
            </select>
        </div>
        <flux:input size="xs" type="number" step="0.01" wire:model="cobroForm.monto_pago" label="{{ __('Monto') }}" required />
        <flux:input size="xs" type="date" wire:model="cobroForm.fecha_pago" label="{{ __('Fecha pago') }}" required />
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Medio de pago') }}</label>
            <select wire:model="cobroForm.payment_method_id"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                <option value="">{{ __('—') }}</option>
                @foreach ($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->nombre }}</option>
                @endforeach
            </select>
        </div>
        <flux:input size="xs" wire:model="cobroForm.numero_operacion" label="{{ __('Nº operación') }}" />
        <flux:input size="xs" wire:model="cobroForm.entidad_financiera" label="{{ __('Entidad') }}" />
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeCobroMatriculaModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs">{{ __('Registrar') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcan

@can('cliente-matriculas.create')
<flux:modal name="crear-plan-cuotas-modal" wire:model="crearPlanCuotasModalAbierto" focusable class="md:max-w-lg">
    <form wire:submit.prevent="guardarCrearPlanCuotas" class="flex max-h-[85vh] flex-col">
        <div class="space-y-3 overflow-y-auto p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Crear plan de cuotas') }}</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Primer cronograma para este cliente. Elige la matrícula en cuotas a la que se asocia el financiamiento.') }}</p>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Matrícula') }}</label>
                <select wire:model.live="crearPlanCuotasMatriculaId"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800" required>
                    @foreach ($matriculasSinCronogramaCuotas as $op)
                        <option value="{{ $op->id }}">{{ $op->nombre }} · #{{ $op->id }} · {{ ucfirst($op->estado) }}</option>
                    @endforeach
                </select>
            </div>
            <flux:input size="xs" type="number" step="0.01" wire:model="crearPlanCuotasForm.monto_total" label="{{ __('Monto total a financiar (S/)') }}" required />
            <flux:input size="xs" type="number" min="2" max="60" wire:model="crearPlanCuotasForm.numero_cuotas" label="{{ __('Número de cuotas') }}" required />
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Frecuencia') }}</label>
                <select wire:model="crearPlanCuotasForm.frecuencia"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                    @foreach (\App\Models\Core\EnrollmentInstallmentPlan::FRECUENCIAS as $k => $v)
                        <option value="{{ $k }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <flux:input size="xs" type="date" wire:model="crearPlanCuotasForm.fecha_inicio" label="{{ __('Fecha inicio') }}" required />
            <flux:textarea size="xs" wire:model="crearPlanCuotasForm.observaciones" label="{{ __('Observaciones') }}" rows="2" />
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeCrearPlanCuotasModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs" wire:loading.attr="disabled" wire:target="guardarCrearPlanCuotas">{{ __('Crear plan') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcan

@can('cliente-matriculas.view')
<flux:modal name="cuotas-cronograma-modal" wire:model="cuotasModalAbierto" focusable flyout variant="floating" class="md:max-w-2xl">
    <div class="space-y-3 p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Cronograma de cuotas') }}</h2>
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full text-xs">
                <thead class="bg-zinc-50 dark:bg-zinc-900">
                    <tr class="text-left text-zinc-500">
                        <th class="px-2 py-2">#</th>
                        <th class="px-2 py-2">{{ __('Vencimiento') }}</th>
                        <th class="px-2 py-2">{{ __('Monto') }}</th>
                        <th class="px-2 py-2">{{ __('Estado') }}</th>
                        <th class="px-2 py-2"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($cuotasModalInstallments as $cuota)
                        <tr>
                            <td class="px-2 py-1.5">{{ $cuota->numero_cuota }}</td>
                            <td class="px-2 py-1.5">{{ optional($cuota->fecha_vencimiento)->format('d/m/Y') }}</td>
                            <td class="px-2 py-1.5">S/ {{ number_format((float) $cuota->monto, 2) }}</td>
                            <td class="px-2 py-1.5">{{ \App\Models\Core\EnrollmentInstallment::ESTADOS[$cuota->estado] ?? ucfirst((string) $cuota->estado) }}</td>
                            <td class="px-2 py-1.5 text-right">
                                @can('cliente-matriculas.update')
                                    @if (in_array($cuota->estado, ['pendiente', 'vencida', 'parcial'], true))
                                        <flux:button size="xs" variant="primary" type="button" wire:click="openRegistrarPagoCuota({{ $cuota->id }})">{{ __('Pagar') }}</flux:button>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-2 py-6 text-center text-zinc-500">{{ __('Sin cuotas registradas.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($cuotasModalMatriculaId)
            <flux:button href="{{ route('clientes.cuotas', ['cliente' => $selectedClienteId, 'matricula' => $cuotasModalMatriculaId]) }}" wire:navigate variant="ghost" size="xs"
                class="h-auto min-h-0 px-0 py-1 text-xs text-violet-600 hover:underline dark:text-violet-400">
                {{ __('Abrir cronograma completo en nueva vista') }}
            </flux:button>
        @endif
    </div>
    <div class="flex justify-end border-t border-zinc-200 p-3 dark:border-zinc-700">
        <flux:button type="button" variant="ghost" size="xs" wire:click="closeCuotasModal">{{ __('Cerrar') }}</flux:button>
    </div>
</flux:modal>
@endcan

@can('cliente-matriculas.update')
<flux:modal name="pago-cuota-modal" wire:model="cuotaPagoModalAbierto" focusable class="md:w-lg">
    <form wire:submit.prevent="guardarPagoCuota" class="space-y-3 p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Registrar pago de cuota') }}</h2>
        <p class="text-xs text-zinc-500">{{ __('Requiere caja abierta. El monto debe coincidir con la cuota programada.') }}</p>
        <flux:input size="xs" type="number" step="0.01" wire:model="pagoCuotaForm.monto" label="{{ __('Monto') }}" required />
        <flux:input size="xs" type="date" wire:model="pagoCuotaForm.fecha_pago" label="{{ __('Fecha') }}" required />
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Medio de pago') }}</label>
            <select wire:model="pagoCuotaForm.payment_method_id"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                <option value="">{{ __('—') }}</option>
                @foreach ($paymentMethods as $pm)
                    <option value="{{ $pm->id }}">{{ $pm->nombre }}</option>
                @endforeach
            </select>
        </div>
        <flux:input size="xs" wire:model="pagoCuotaForm.numero_operacion" label="{{ __('Nº operación') }}" />
        <flux:input size="xs" wire:model="pagoCuotaForm.entidad_financiera" label="{{ __('Entidad') }}" />
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeCuotaPagoModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs">{{ __('Registrar pago') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcan

@canany(['rentals.create', 'rentals.update'])
<flux:modal name="reserva-cliente-modal" wire:model="reservaModalAbierto" focusable class="md:w-lg">
    <form wire:submit.prevent="guardarReserva" class="space-y-3 p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
            {{ $editingRentalId ? __('Editar reserva') : __('Nueva reserva') }}
        </h2>
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Espacio') }}</label>
            <select wire:model="reservaForm.rentable_space_id"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800" required>
                <option value="">{{ __('Selecciona…') }}</option>
                @foreach ($rentableSpaces as $sp)
                    <option value="{{ $sp->id }}">{{ $sp->nombre }}</option>
                @endforeach
            </select>
        </div>
        <flux:input size="xs" type="date" wire:model="reservaForm.fecha" label="{{ __('Fecha') }}" required />
        <div class="grid grid-cols-2 gap-2">
            <flux:input size="xs" type="time" wire:model="reservaForm.hora_inicio" label="{{ __('Inicio') }}" required />
            <flux:input size="xs" type="time" wire:model="reservaForm.hora_fin" label="{{ __('Fin') }}" required />
        </div>
        <flux:input size="xs" type="number" step="0.01" wire:model="reservaForm.precio" label="{{ __('Precio') }}" required />
        <div>
            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Estado') }}</label>
            <select wire:model="reservaForm.estado"
                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800">
                <option value="reservado">{{ __('Reservado') }}</option>
                <option value="confirmado">{{ __('Confirmado') }}</option>
                <option value="pagado">{{ __('Pagado') }}</option>
                <option value="cancelado">{{ __('Cancelado') }}</option>
                <option value="finalizado">{{ __('Finalizado') }}</option>
            </select>
        </div>
        <flux:textarea wire:model="reservaForm.observaciones" rows="2" label="{{ __('Observaciones') }}" />
        <div class="flex justify-end gap-2 pt-2">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeReservaModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs">{{ __('Guardar') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcanany

<flux:modal name="fidelizacion-historial-modal" wire:model="fidelizacionHistorialModalAbierto" focusable class="md:max-w-lg">
    <div class="flex max-h-[80vh] flex-col">
        <div class="border-b border-zinc-200 p-4 dark:border-zinc-700">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Historial de fidelización') }}</h2>
        </div>
        <div class="min-h-0 flex-1 overflow-y-auto p-4">
            <div class="divide-y divide-zinc-200 rounded-xl border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                @forelse ($fidelizacionMensajes as $msg)
                    <div class="p-3">
                        <p class="text-xs font-bold text-zinc-900 dark:text-zinc-100">
                            {{ __('Incidencia:') }}
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300">{{ $msg->prioridad_label }}</span>
                        </p>
                        <p class="mt-1 text-xs text-zinc-700 dark:text-zinc-300">{{ $msg->mensaje }}</p>
                        <div class="mt-2 flex items-center justify-between border-t border-zinc-100 pt-2 text-[11px] text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                            <span>{{ $msg->autor?->name ?? '—' }}</span>
                            <span class="tabular-nums">{{ $msg->created_at->locale('es')->format('d/m/Y') }} · {{ $msg->created_at->format('g:i A') }}</span>
                        </div>
                    </div>
                @empty
                    <p class="p-6 text-center text-xs text-zinc-500 dark:text-zinc-400">{{ __('Sin mensajes de fidelización.') }}</p>
                @endforelse
            </div>
        </div>
        <div class="flex justify-end border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeFidelizacionHistorialModal">{{ __('Cerrar') }}</flux:button>
        </div>
    </div>
</flux:modal>

@can('clientes.update')
<flux:modal name="fidelizacion-nuevo-modal" wire:model="fidelizacionNuevoModalAbierto" focusable class="md:max-w-lg">
    <form wire:submit.prevent="guardarFidelizacionMensaje" class="flex max-h-[85vh] flex-col">
        <div class="space-y-3 overflow-y-auto p-4">
            <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Nuevo mensaje de fidelización') }}</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Se registrará con la fecha y hora actual. Autor:') }}
                <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ auth()->user()?->name ?? '—' }}</span>
            </p>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">{{ __('Prioridad') }}</label>
                <select wire:model="fidelizacionForm.prioridad"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-xs dark:border-zinc-600 dark:bg-zinc-800" required>
                    @foreach (\App\Models\Core\ClienteFidelizacionMensaje::PRIORIDADES as $valor => $etiqueta)
                        <option value="{{ $valor }}">{{ $etiqueta }}</option>
                    @endforeach
                </select>
                @error('fidelizacionForm.prioridad')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <flux:textarea size="xs" wire:model="fidelizacionForm.mensaje" label="{{ __('Mensaje') }}" rows="4" required />
                @error('fidelizacionForm.mensaje')
                    <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeFidelizacionNuevoModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs" wire:loading.attr="disabled" wire:target="guardarFidelizacionMensaje">{{ __('Guardar') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcan
