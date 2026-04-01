@canany(['cliente-matriculas.create', 'cliente-matriculas.update'])
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
                    <flux:input size="xs" wire:model="matriculaForm.fecha_inicio" label="Fecha Inicio" type="date"
                        required />
                    @error('matriculaForm.fecha_inicio')
                        <p class="mt-0.5 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                @if ($matriculaForm['tipo'] === 'membresia')
                    <div>
                        <flux:input size="xs" wire:model="matriculaForm.fecha_fin" label="Fecha Fin" type="date" required />
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
                @php
                    $esFrecPersonalizada = ($matriculaForm['frecuencia_cuotas'] ?? '') === 'personalizado';
                    $porMontoCuota = ($matriculaForm['personalizado_por'] ?? 'numero_cuotas') === 'monto_cuota';
                    $montoObjPer = (float) ($matriculaForm['monto_cuota_personalizado'] ?? 0);
                    $membresiaSeleccionada = filled($matriculaForm['membresia_id'] ?? null);
                @endphp
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
                    <div class="grid grid-cols-2 gap-2">
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
                        <div>
                            <flux:input size="xs" wire:model.live.number="matriculaForm.cuota_inicial_monto" label="{{ __('Cuota inicial (S/)') }}"
                                type="number" step="0.01" min="0"
                                @disabled(($matriculaForm['modalidad_pago'] ?? 'contado') !== 'cuotas' || ($clienteMatriculaId && ($matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas')) />
                            @if (($matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas')
                                <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                    {{ __('No se cobra al matricular: la cuota inicial se registra como deuda pendiente en el plan de cuotas.') }}
                                </p>
                            @endif
                            <flux:error name="matriculaForm.cuota_inicial_monto" />
                        </div>
                    </div>

                    @if (($matriculaForm['modalidad_pago'] ?? 'contado') === 'cuotas')
                        @if ($esFrecPersonalizada && ! $matriculaBloqueaNumeroCuotas)
                            <div class="mt-2 rounded-lg border border-dashed border-zinc-300 p-2 dark:border-zinc-600">
                                <p class="mb-1.5 text-[11px] font-medium text-zinc-700 dark:text-zinc-300">{{ __('Plan personalizado') }}</p>
                                <div class="flex flex-wrap gap-3 text-xs text-zinc-700 dark:text-zinc-300">
                                    <label class="inline-flex cursor-pointer items-center gap-1.5">
                                        <input type="radio" wire:model.live="matriculaForm.personalizado_por" value="numero_cuotas" class="rounded-full border-zinc-300 text-violet-600" />
                                        {{ __('Por número de cuotas') }}
                                    </label>
                                    <label class="inline-flex cursor-pointer items-center gap-1.5">
                                        <input type="radio" wire:model.live="matriculaForm.personalizado_por" value="monto_cuota" class="rounded-full border-zinc-300 text-violet-600" />
                                        {{ __('Por monto por cuota') }}
                                    </label>
                                </div>
                            </div>
                        @endif
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            <div>
                                @if ($esFrecPersonalizada && $porMontoCuota)
                                    <flux:input size="xs" wire:model.live.number="matriculaForm.monto_cuota_personalizado" label="{{ __('Monto por cuota (S/)') }}"
                                        type="number" step="0.01" min="0.01" @disabled($matriculaBloqueaNumeroCuotas) />
                                    <flux:error name="matriculaForm.monto_cuota_personalizado" />
                                    @if ($montoObjPer > 0 && $this->matriculaSaldoFinanciado > 0)
                                        <p class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                            {{ __('Se generarán :n cuotas (máx. 60).', ['n' => $this->matriculaNumeroCuotasEstimado]) }}
                                        </p>
                                    @endif
                                @else
                                    <flux:input size="xs" wire:model.live.number="matriculaForm.numero_cuotas" label="{{ __('Número de cuotas') }}"
                                        type="number" min="2" max="60" @disabled($matriculaBloqueaNumeroCuotas) />
                                    <flux:error name="matriculaForm.numero_cuotas" />
                                @endif
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-zinc-700 dark:text-zinc-300">
                                    Frecuencia
                                </label>
                                <select wire:model.live="matriculaForm.frecuencia_cuotas"
                                    @disabled($matriculaBloqueaNumeroCuotas)
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-2 py-1 text-xs text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 disabled:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:disabled:bg-zinc-700">
                                    @foreach (\App\Models\Core\EnrollmentInstallmentPlan::FRECUENCIAS as $k => $label)
                                        <option value="{{ $k }}">{{ __($label) }}</option>
                                    @endforeach
                                </select>
                                <flux:error name="matriculaForm.frecuencia_cuotas" />
                            </div>
                            <div>
                                <flux:input size="xs" wire:model="matriculaForm.fecha_inicio_plan_cuotas"
                                    label="{{ __('Inicio del plan') }}" type="date" @disabled($matriculaBloqueaNumeroCuotas) />
                                <flux:error name="matriculaForm.fecha_inicio_plan_cuotas" />
                            </div>
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2 rounded-lg bg-zinc-50 p-2 dark:bg-zinc-900/40">
                            <div class="text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">Saldo financiado:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100"> S/ {{ number_format($this->matriculaSaldoFinanciado, 2) }}</span>
                            </div>
                            <div class="text-xs">
                                <span class="text-zinc-500 dark:text-zinc-400">Cuota estimada:</span>
                                <span class="font-medium text-zinc-900 dark:text-zinc-100"> S/ {{ number_format($this->matriculaCuotaEstimada, 2) }}</span>
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
            @if (auth()->check() && ($clienteMatriculaId ? auth()->user()->can('cliente-matriculas.update') : auth()->user()->can('cliente-matriculas.create')))
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

@can('cliente-matriculas.delete')
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

@can('cliente-matriculas.update')
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
