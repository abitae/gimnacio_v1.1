<div class="space-y-6">
    <!-- Header -->
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="arrow-path" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Sincronizar BioTime</h1>
                    <p class="text-sm text-white/90">Sincronizar empleados y transacciones (asistencia)</p>
                </div>
            </div>
            <a href="{{ route('biotime.index') }}" wire:navigate
                class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white hover:bg-white/20">
                Volver al dashboard
            </a>
        </div>
    </div>


    <!-- Tabs -->
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button type="button" wire:click="switchTab('employees')"
                class="px-6 py-3 text-sm font-medium {{ $tab === 'employees' ? 'border-b-2 border-purple-600 text-purple-600 dark:text-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Empleados
            </button>
            <button type="button" wire:click="switchTab('upload')"
                class="px-6 py-3 text-sm font-medium {{ $tab === 'upload' ? 'border-b-2 border-purple-600 text-purple-600 dark:text-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Subir clientes a BioTime
            </button>
            <button type="button" wire:click="switchTab('transactions')"
                class="px-6 py-3 text-sm font-medium {{ $tab === 'transactions' ? 'border-b-2 border-purple-600 text-purple-600 dark:text-purple-400' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}">
                Sincronizar transacciones
            </button>
        </div>

        <div class="p-6">
            @if ($tab === 'upload')
                <div class="space-y-4">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Sincroniza los clientes activos con BioTime en un solo proceso. El <strong>id del empleado en BioTime es igual al id del cliente</strong>. Si el empleado con ese id ya existe en BioTime se actualizará; si no existe se creará. Departamento y área se usan solo para los que se crean nuevos.
                    </p>
                    <form wire:submit="syncClientesToBiotime" class="space-y-4">
                        <div class="flex flex-wrap gap-4">
                            <flux:field class="min-w-[200px]">
                                <flux:label>Departamento (BioTime)</flux:label>
                                <select wire:model="uploadDepartmentId" required
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="">Seleccionar...</option>
                                    @foreach ($departmentsList as $dept)
                                        <option value="{{ $dept['id'] ?? '' }}">{{ $dept['dept_name'] ?? $dept['dept_code'] ?? $dept['id'] }}</option>
                                    @endforeach
                                </select>
                                <flux:error name="uploadDepartmentId" />
                            </flux:field>
                            <flux:field class="min-w-[200px]">
                                <flux:label>Área (BioTime)</flux:label>
                                <select wire:model="uploadAreaId" required
                                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                                    <option value="">Seleccionar...</option>
                                    @foreach ($areasList as $area)
                                        <option value="{{ $area['id'] ?? '' }}">{{ $area['area_name'] ?? $area['area_code'] ?? $area['id'] }}</option>
                                    @endforeach
                                </select>
                                <flux:error name="uploadAreaId" />
                            </flux:field>
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            Clientes activos a sincronizar: <strong>{{ $this->clientesActivos->count() }}</strong>
                        </p>
                        @if ($this->clientesActivos->count() > 0)
                            <p class="text-xs text-zinc-500 dark:text-zinc-400">También puedes sincronizar uno por uno con el botón de cada fila.</p>
                            <div class="max-h-64 overflow-y-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                                <ul class="divide-y divide-zinc-200 dark:divide-zinc-700 p-2 text-sm">
                                    @foreach ($this->clientesActivos->take(30) as $c)
                                        <li class="flex items-center justify-between gap-2 py-1.5 text-zinc-700 dark:text-zinc-300">
                                            <span>{{ $c->nombres }} {{ $c->apellidos }} — {{ $c->numero_documento }} (id: {{ $c->id }})</span>
                                            <flux:button type="button" size="xs" variant="ghost" color="purple" wire:click="syncClienteToBiotime({{ $c->id }})" wire:loading.attr="disabled" wire:target="syncClienteToBiotime">
                                                <span wire:loading.remove wire:target="syncClienteToBiotime">Sincronizar</span>
                                                <span wire:loading wire:target="syncClienteToBiotime">...</span>
                                            </flux:button>
                                        </li>
                                    @endforeach
                                    @if ($this->clientesActivos->count() > 30)
                                        <li class="py-1 text-zinc-500">... y {{ $this->clientesActivos->count() - 30 }} más</li>
                                    @endif
                                </ul>
                            </div>
                            <flux:button type="submit" color="purple" variant="primary" wire:loading.attr="disabled" wire:target="syncClientesToBiotime">
                                <span wire:loading.remove wire:target="syncClientesToBiotime">Sincronizar {{ $this->clientesActivos->count() }} cliente(s) con BioTime</span>
                                <span wire:loading wire:target="syncClientesToBiotime">Sincronizando...</span>
                            </flux:button>
                        @else
                            <p class="text-amber-600 dark:text-amber-400">No hay clientes activos para sincronizar.</p>
                        @endif
                    </form>
                    @if ($uploadMessage !== '')
                        <div class="rounded-lg p-4 {{ $uploadResult ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                            {{ $uploadMessage }}
                        </div>
                    @endif
                </div>
            @elseif ($tab === 'employees')
                <div class="space-y-4">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Lista de empleados desde BioTime. El código coincide con el id del cliente cuando está sincronizado.
                    </p>
                    <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-900">
                                <tr>
                                    <th class="px-4 py-2 text-left font-medium text-zinc-700 dark:text-zinc-300">Código</th>
                                    <th class="px-4 py-2 text-left font-medium text-zinc-700 dark:text-zinc-300">Nombre</th>
                                    <th class="px-4 py-2 text-left font-medium text-zinc-700 dark:text-zinc-300">Cliente (local)</th>
                                    <th class="px-4 py-2 text-left font-medium text-zinc-700 dark:text-zinc-300">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                @forelse ($employees as $emp)
                                    @php
                                        $empCode = $emp['emp_code'] ?? (string)($emp['id'] ?? '');
                                        $empCodeNumeric = $empCode !== null && $empCode !== '' && is_numeric($empCode);
                                        $cliente = $empCodeNumeric ? \App\Models\Core\Cliente::find((int) $empCode) : null;
                                        $firstName = trim($emp['first_name'] ?? '');
                                        $lastName = trim($emp['last_name'] ?? '');
                                    @endphp
                                    <tr>
                                        <td class="px-4 py-2 font-mono text-zinc-900 dark:text-zinc-100">{{ $empCode }}</td>
                                        <td class="px-4 py-2 text-zinc-900 dark:text-zinc-100">
                                            {{ ($firstName . ' ' . $lastName) ?: '-' }}
                                        </td>
                                        <td class="px-4 py-2 text-zinc-600 dark:text-zinc-400">
                                            @if ($cliente)
                                                {{ $cliente->nombres }} {{ $cliente->apellidos }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 flex flex-wrap gap-1">
                                            @if (!$cliente && $empCodeNumeric)
                                                <flux:button type="button" size="xs" variant="ghost" color="purple"
                                                    wire:click="createClienteFromBiotimeEmployee({{ (int) $empCode }}, '{{ addslashes($firstName) }}', '{{ addslashes($lastName) }}')"
                                                    wire:loading.attr="disabled"
                                                    wire:target="createClienteFromBiotimeEmployee">
                                                    <span wire:loading.remove wire:target="createClienteFromBiotimeEmployee">Actualizar cliente</span>
                                                    <span wire:loading wire:target="createClienteFromBiotimeEmployee">...</span>
                                                </flux:button>
                                            @endif
                                            @php
                                                $biotimeInternalId = (int)($emp['id'] ?? 0);
                                            @endphp
                                            @if ($biotimeInternalId > 0)
                                                <flux:button type="button" size="xs" variant="ghost" color="red"
                                                    wire:click="deleteEmployeeFromBiotime({{ $biotimeInternalId }}, {{ $empCodeNumeric ? (int) $empCode : 0 }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="deleteEmployeeFromBiotime"
                                                    title="Eliminar este empleado de BioTime">
                                                    <span wire:loading.remove wire:target="deleteEmployeeFromBiotime">Eliminar de BioTime</span>
                                                    <span wire:loading wire:target="deleteEmployeeFromBiotime">...</span>
                                                </flux:button>
                                            @endif
                                            @if (!$empCodeNumeric)
                                                —
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-zinc-500 dark:text-zinc-400">
                                            No hay empleados o no se pudo cargar la lista. Comprueba la configuración.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($syncMessage && $tab === 'employees')
                        <p class="text-sm text-amber-600 dark:text-amber-400">{{ $syncMessage }}</p>
                    @endif
                </div>
            @elseif ($tab === 'transactions')
                <div class="space-y-4">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        Descarga transacciones (punch) desde BioTime en el rango de fechas indicado y crea registros en BiotimeAccessLog y Asistencia (origen biotime) para los clientes sincronizados.
                    </p>
                    <form wire:submit="syncTransactions" class="flex flex-wrap items-end gap-4">
                        <flux:field>
                            <flux:label>Desde</flux:label>
                            <flux:input type="date" wire:model="syncStartDate" />
                            <flux:error name="syncStartDate" />
                        </flux:field>
                        <flux:field>
                            <flux:label>Hasta</flux:label>
                            <flux:input type="date" wire:model="syncEndDate" />
                            <flux:error name="syncEndDate" />
                        </flux:field>
                        <flux:button type="submit" color="purple" variant="primary" wire:loading.attr="disabled" wire:target="syncTransactions">
                            <span wire:loading.remove wire:target="syncTransactions">Sincronizar transacciones</span>
                            <span wire:loading wire:target="syncTransactions">Sincronizando...</span>
                        </flux:button>
                    </form>
                    @if ($syncMessage !== '')
                        <div class="rounded-lg p-4 {{ $syncResult ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                            {{ $syncMessage }}
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>

</div>
