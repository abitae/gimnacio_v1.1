<div class="space-y-6">
    <!-- Header -->
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="signal" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Integración BioTime</h1>
                    <p class="text-sm text-white/90">ZKTeco / BioTime: personal, dispositivos y asistencia</p>
                </div>
            </div>
            <flux:button variant="ghost" size="sm" wire:click="checkConnection"
                wire:loading.attr="disabled" wire:target="checkConnection"
                class="bg-white/10 text-white hover:bg-white/20 border-0">
                <span wire:loading.remove wire:target="checkConnection">Comprobar conexión</span>
                <span wire:loading wire:target="checkConnection">Comprobando...</span>
            </flux:button>
        </div>
    </div>

    <!-- Connection status -->
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <h2 class="mb-4 text-lg font-semibold text-zinc-900 dark:text-zinc-100">Estado de la conexión</h2>
        @if ($connectionStatus === null)
            <p class="text-zinc-500 dark:text-zinc-400">Cargando...</p>
        @elseif ($connectionStatus)
            <div class="flex items-center gap-3 rounded-lg bg-green-50 p-4 dark:bg-green-900/20">
                <flux:icon name="check-circle" class="h-8 w-8 text-green-600 dark:text-green-400" />
                <div>
                    <p class="font-medium text-green-800 dark:text-green-300">Conexión correcta</p>
                    <p class="text-sm text-green-700 dark:text-green-400">BioTime responde correctamente. Puedes sincronizar y gestionar datos.</p>
                </div>
            </div>
        @else
            <div class="flex items-center gap-3 rounded-lg bg-red-50 p-4 dark:bg-red-900/20">
                <flux:icon name="exclamation-triangle" class="h-8 w-8 text-red-600 dark:text-red-400" />
                <div class="flex-1">
                    <p class="font-medium text-red-800 dark:text-red-300">Sin conexión</p>
                    <p class="text-sm text-red-700 dark:text-red-400">{{ $connectionError }}</p>
                    <a href="{{ route('biotime.config') }}" wire:navigate
                        class="mt-2 inline-block text-sm font-medium text-red-600 underline hover:no-underline dark:text-red-400">
                        Ir a configuración
                    </a>
                </div>
            </div>
        @endif
    </div>

    <!-- Quick links -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <a href="{{ route('biotime.config') }}" wire:navigate
            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <flux:icon name="cog-6-tooth" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Configuración</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">URL, usuario y tipo de autenticación</p>
            </div>
        </a>
        <a href="{{ route('biotime.sync') }}" wire:navigate
            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <flux:icon name="arrow-path" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Sincronizar</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Empleados y transacciones (asistencia)</p>
            </div>
        </a>
        <a href="{{ route('biotime.areas') }}" wire:navigate
            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <flux:icon name="map-pin" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Áreas</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Listar, crear, editar y eliminar áreas</p>
            </div>
        </a>
        <a href="{{ route('biotime.departments') }}" wire:navigate
            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <flux:icon name="building-office-2" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Departamentos</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Listar, crear, editar y eliminar departamentos</p>
            </div>
        </a>
        <a href="{{ route('biotime.employees') }}" wire:navigate
            class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm transition hover:border-purple-300 hover:shadow dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-purple-600">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-purple-100 dark:bg-purple-900/30">
                <flux:icon name="users" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
                <h3 class="font-semibold text-zinc-900 dark:text-zinc-100">Empleados</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Listar, crear, editar y eliminar empleados</p>
            </div>
        </a>
        <div class="flex items-center gap-4 rounded-xl border border-zinc-200 bg-zinc-50 p-6 dark:border-zinc-700 dark:bg-zinc-800/50">
            <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-zinc-200 dark:bg-zinc-700">
                <flux:icon name="cpu-chip" class="h-6 w-6 text-zinc-500 dark:text-zinc-400" />
            </div>
            <div>
                <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">Dispositivos</h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Próximamente: listado de terminales</p>
            </div>
        </div>
    </div>
</div>
