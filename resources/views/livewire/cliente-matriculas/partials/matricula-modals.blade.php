@canany(['matricula_cliente.crear', 'matricula_cliente.editar'])
<!-- Create/Edit Modal -->
<flux:modal name="create-edit-modal" wire:model="matriculaModalState.create" focusable flyout variant="floating"
    class="md:w-lg">
    <form wire:submit.prevent="save">
        <div class="space-y-3 p-4">
            <div>
                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $clienteMatriculaId ? 'Editar Matrícula' : 'Nueva Matrícula' }}
                </h2>
                <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                    {{ $clienteMatriculaId ? 'Modifica la información de la matrícula' : 'Asigna una nueva ' . ($matriculaForm['tipo'] === 'membresia' ? 'membresía' : 'clase') . ' al cliente' }}
                </p>
            </div>

            @if (!$clienteMatriculaId)
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="matriculaForm.tipo"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="membresia">Membresía</option>
                        <option value="clase">Clase</option>
                    </select>
                    <flux:error name="matriculaForm.tipo" />
                </div>
            @endif

            @if ($matriculaForm['tipo'] === 'membresia')
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Membresía <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="matriculaForm.membresia_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Selecciona una membresía</option>
                        @foreach ($membresiasActivas as $membresia)
                            <option value="{{ $membresia->id }}">{{ $membresia->nombre }} - S/
                                {{ number_format($membresia->precio_base, 2) }}</option>
                        @endforeach
                    </select>
                    <flux:error name="matriculaForm.membresia_id" />
                </div>
            @else
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Clase <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="matriculaForm.clase_id"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="">Selecciona una clase</option>
                        @foreach ($clasesActivas as $clase)
                            <option value="{{ $clase->id }}">{{ $clase->nombre }} - S/
                                {{ number_format($clase->obtenerPrecio(), 2) }}</option>
                        @endforeach
                    </select>
                    <flux:error name="matriculaForm.clase_id" />
                </div>
            @endif

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <flux:input size="xs" wire:model="matriculaForm.fecha_matricula" label="Fecha Matrícula" type="date"
                        required />
                    @error('matriculaForm.fecha_matricula')
                        <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <flux:input size="xs" wire:model.live="matriculaForm.fecha_inicio" label="Fecha Inicio" type="date"
                        required />
                    @error('matriculaForm.fecha_inicio')
                        <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @if ($matriculaForm['tipo'] === 'membresia')
                    <div>
                        <flux:input size="xs" wire:model="matriculaForm.fecha_fin" label="{{ __('Fecha fin (según plan)') }}"
                            type="date" readonly
                            class="cursor-not-allowed opacity-90" />
                        <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Se calcula con los días del plan a partir de la fecha de inicio.') }}</p>
                        <flux:error name="matriculaForm.fecha_fin" />
                    </div>
                @else
                    <div>
                        <flux:input size="xs" wire:model.number="matriculaForm.sesiones_totales" label="Sesiones Totales"
                            type="number" min="1" />
                        <flux:error name="matriculaForm.sesiones_totales" />
                    </div>
                @endif
            </div>

            <div class="grid grid-cols-3 gap-2">
                <div>
                    <flux:input size="xs" wire:model.live.number="matriculaForm.precio_lista" label="Precio Lista (S/)"
                        type="number" step="0.01" min="0" required />
                    <flux:error name="matriculaForm.precio_lista" />
                </div>

                <div>
                    <flux:input size="xs" wire:model.live.number="matriculaForm.descuento_monto" label="Descuento (S/)"
                        type="number" step="0.01" min="0" />
                    <flux:error name="matriculaForm.descuento_monto" />
                </div>

                <div>
                    <flux:input size="xs" wire:model.number="matriculaForm.precio_final" label="Precio Final (S/)"
                        type="number" step="0.01" min="0" readonly />
                    <flux:error name="matriculaForm.precio_final" />
                </div>
            </div>

            @if ($matriculaForm['tipo'] === 'membresia')
                @php($membresiaSeleccionada = filled($matriculaForm['membresia_id'] ?? null))
                <div class="rounded-lg border border-zinc-200 p-2.5 dark:border-zinc-700">
                    <div class="mb-2 flex items-center justify-between">
                        <h3 class="text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Pago de membresía') }}</h3>
                        @if ($clienteMatriculaId && ($matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas')
                            <span class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ __('Plan de cuotas generado') }}</span>
                        @endif
                    </div>

                    @if (! $membresiaSeleccionada)
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Seleccione una membresía para indicar modalidad de pago y número de cuotas.') }}</p>
                    @else
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                {{ __('Modalidad de pago') }}
                            </label>
                            <select wire:model.live="matriculaForm.modalidad_pago"
                                @disabled($clienteMatriculaId && ($matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas')
                                class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 disabled:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:disabled:bg-zinc-700">
                                <option value="contado">{{ __('Contado') }}</option>
                                <option value="cuotas">{{ __('Cuotas') }}</option>
                            </select>
                            <flux:error name="matriculaForm.modalidad_pago" />
                        </div>
                    </div>

                    @if (($matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas')
                        @php($puedeEditarCronogramaCuotasModal = ! $clienteMatriculaId)
                        <div class="mt-2 rounded-lg border border-violet-200 bg-violet-50/80 px-3 py-2 text-[11px] text-violet-900 dark:border-violet-900/60 dark:bg-violet-950/20 dark:text-violet-200">
                            {{ __('Indica frecuencia y monto inicial: el resto de cuotas se calcula sobre el saldo. En una matrícula nueva puedes corregir montos y vencimientos en la tabla; al guardar, la suma debe igualar el precio final.') }}
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    {{ __('Frecuencia') }}
                                </label>
                                <select wire:model.live="matriculaForm.frecuencia_cuotas"
                                    @disabled($matriculaBloqueaNumeroCuotas)
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 disabled:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:disabled:bg-zinc-700">
                                    <option value="quincenal">{{ __('Cada 15 días') }}</option>
                                    <option value="mensual">{{ __('Mensual') }}</option>
                                </select>
                                <flux:error name="matriculaForm.frecuencia_cuotas" />
                            </div>
                            <div>
                                <flux:input size="xs" wire:model.live.number="matriculaForm.cuota_inicial_monto" label="{{ __('Monto inicial / 1.ª cuota (S/)') }}"
                                    type="number" step="0.01" min="0"
                                    @disabled($matriculaBloqueaNumeroCuotas) />
                                <flux:error name="matriculaForm.cuota_inicial_monto" />
                            </div>
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-2 sm:grid-cols-2">
                            <div>
                                <flux:input size="xs" wire:model.live.number="matriculaForm.numero_cuotas" label="{{ __('Número de cuotas') }}"
                                    type="number" min="2" max="60" @disabled($matriculaBloqueaNumeroCuotas) />
                                <flux:error name="matriculaForm.numero_cuotas" />
                            </div>
                            <div>
                                <flux:input size="xs" wire:model.live="matriculaForm.fecha_inicio_plan_cuotas"
                                    label="{{ __('Inicio del plan') }}" type="date" @disabled($matriculaBloqueaNumeroCuotas) />
                                <flux:error name="matriculaForm.fecha_inicio_plan_cuotas" />
                            </div>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900/40">
                            <div class="text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">Saldo restante:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100"> S/ {{ number_format($this->matriculaSaldoFinanciado, 2) }}</span>
                            </div>
                            <div class="text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">Otras cuotas estimadas:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100"> S/ {{ number_format($this->matriculaCuotaEstimada, 2) }}</span>
                            </div>
                        </div>
                        <div class="mt-2 grid grid-cols-1 gap-2 rounded-lg border border-zinc-200 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900/30">
                            <div class="grid gap-2 sm:grid-cols-3">
                                <div class="rounded-lg bg-zinc-50 px-3 py-2 text-xs dark:bg-zinc-800/70">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Saldo restante') }}</span>
                                    <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($this->matriculaSaldoFinanciado, 2) }}</p>
                                </div>
                                <div class="rounded-lg bg-zinc-50 px-3 py-2 text-xs dark:bg-zinc-800/70">
                                    <span class="text-zinc-500 dark:text-zinc-400">{{ __('Suma cronograma') }}</span>
                                    <p class="mt-1 font-semibold text-zinc-900 dark:text-zinc-100">S/ {{ number_format($this->matriculaSumaCronogramaPreview, 2) }}</p>
                                </div>
                                <div class="rounded-lg px-3 py-2 text-xs {{ $this->matriculaCronogramaPreviewCuadra ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300' }}">
                                    <span>{{ __('Validación') }}</span>
                                    <p class="mt-1 font-semibold">
                                        {{ $this->matriculaCronogramaPreviewCuadra ? __('El cronograma cuadra con el monto total.') : __('El cronograma aún no cuadra.') }}
                                    </p>
                                </div>
                            </div>

                            <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <table class="min-w-full text-xs">
                                    <thead class="bg-zinc-50 dark:bg-zinc-900">
                                        <tr class="text-left text-zinc-500 dark:text-zinc-400">
                                            <th class="px-2 py-2">#</th>
                                            <th class="px-2 py-2">{{ __('Fecha vencimiento') }}</th>
                                            <th class="px-2 py-2 text-right">{{ __('Monto') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                        @forelse ($matriculaSchedulePreview as $idx => $cuotaPreview)
                                            <tr wire:key="matricula-crono-{{ $idx }}-{{ $cuotaPreview['fecha_vencimiento'] ?? $idx }}">
                                                <td class="px-2 py-1.5 font-medium text-zinc-900 dark:text-zinc-100">{{ $cuotaPreview['numero_cuota'] ?? ($idx + 1) }}</td>
                                                <td class="px-2 py-1.5 text-zinc-700 dark:text-zinc-300">
                                                    @if ($puedeEditarCronogramaCuotasModal)
                                                        <input type="date" wire:model.blur="matriculaSchedulePreview.{{ $idx }}.fecha_vencimiento"
                                                            class="w-full rounded border border-zinc-300 bg-white px-1 py-0.5 text-xs text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" />
                                                    @else
                                                        {{ \Carbon\Carbon::parse($cuotaPreview['fecha_vencimiento'])->format('d/m/Y') }}
                                                    @endif
                                                </td>
                                                <td class="px-2 py-1.5 text-right tabular-nums text-zinc-900 dark:text-zinc-100">
                                                    @if ($puedeEditarCronogramaCuotasModal)
                                                        <span class="mr-0.5 text-zinc-500">S/</span>
                                                        <input type="number" step="0.01" min="0" wire:model.blur="matriculaSchedulePreview.{{ $idx }}.monto"
                                                            wire:blur="onBlurMatriculaSchedulePreviewMonto({{ $idx }})"
                                                            class="inline-block w-[5.5rem] rounded border border-zinc-300 bg-white px-1 py-0.5 text-right text-xs text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" />
                                                    @else
                                                        S/ {{ number_format((float) $cuotaPreview['monto'], 2) }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="px-2 py-4 text-center text-zinc-500 dark:text-zinc-400">
                                                    {{ __('Completa monto inicial, fecha de inicio del plan, frecuencia y número de cuotas para ver el cronograma.') }}
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-2 gap-2">
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Estado
                    </label>
                    <select wire:model="matriculaForm.estado"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        @foreach (['activa' => __('Activa'), 'vencida' => __('Vencida'), 'cancelada' => __('Cancelada'), 'completada' => __('Completada')] as $val => $label)
                            <option value="{{ $val }}">{{ $label }}</option>
                        @endforeach
                        @if (($matriculaForm['estado'] ?? '') === 'congelada')
                            <option value="congelada">{{ __('Congelada') }}</option>
                        @endif
                    </select>
                    <flux:error name="matriculaForm.estado" />
                </div>

                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Canal de Venta
                    </label>
                    <select wire:model="matriculaForm.canal_venta"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                        <option value="presencial">Presencial</option>
                        <option value="online">Online</option>
                        <option value="telefonico">Telefónico</option>
                        <option value="referido">Referido</option>
                    </select>
                    <flux:error name="matriculaForm.canal_venta" />
                </div>
            </div>

            @if ($matriculaForm['estado'] === 'cancelada')
                <div>
                    <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                        Motivo de Cancelación
                    </label>
                    <textarea wire:model="matriculaForm.motivo_cancelacion" rows="2"
                        class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"></textarea>
                    <flux:error name="matriculaForm.motivo_cancelacion" />
                </div>
            @endif
        </div>

        <div class="flex justify-end gap-2 border-t border-zinc-200 p-3 dark:border-zinc-700">
            <flux:modal.close>
                <flux:button variant="ghost" size="xs" wire:click="closeModal" type="button">
                    Cancelar
                </flux:button>
            </flux:modal.close>
            @if (auth()->check() && ($clienteMatriculaId ? auth()->user()->can('matricula_cliente.editar') : auth()->user()->can('matricula_cliente.crear')))
                <flux:button variant="primary" size="xs" type="submit" wire:loading.attr="disabled"
                    wire:target="save">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="save" />
                        <span wire:loading.remove wire:target="save">{{ $clienteMatriculaId ? 'Actualizar' : 'Crear' }}</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
            @endif
        </div>
    </form>
</flux:modal>
@endcanany

@can('matricula_cliente.eliminar')
<!-- Delete Modal -->
<flux:modal name="delete-modal" wire:model="matriculaModalState.delete" focusable class="md:w-lg">
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

@can('matricula_cliente.editar')
<flux:modal name="matricula-congelar-modal" wire:model="matriculaCongelarModalOpen" focusable class="md:w-md">
    <form wire:submit.prevent="saveCongelarMatricula" class="space-y-3 p-4">
        <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">{{ __('Congelar membresía') }}</h2>
        <p class="text-xs text-zinc-600 dark:text-zinc-400">
            {{ __('Se extiende la fecha de fin y el cronograma de cuotas pendientes según los días indicados.') }}
            @if ($matriculaCongelarMaxDias !== null)
                <span class="mt-1 block font-medium text-zinc-700 dark:text-zinc-300">{{ __('Máximo permitido por plan: :d días', ['d' => $matriculaCongelarMaxDias]) }}</span>
            @endif
        </p>
        <flux:input size="xs" type="number" wire:model="matriculaCongelarDias" min="1"
            max="{{ $matriculaCongelarMaxDias ?? 365 }}"
            label="{{ __('Días de congelación') }}" required />
        <flux:textarea wire:model="matriculaCongelarMotivo" rows="2" label="{{ __('Motivo (opcional)') }}" />
        <div class="flex justify-end gap-2 border-t border-zinc-200 pt-3 dark:border-zinc-700">
            <flux:button type="button" variant="ghost" size="xs" wire:click="closeCongelarMatriculaModal">{{ __('Cancelar') }}</flux:button>
            <flux:button type="submit" variant="primary" size="xs">{{ __('Congelar') }}</flux:button>
        </div>
    </form>
</flux:modal>
@endcan
