<div class="space-y-4 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">Reportes</h1>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">Selecciona el tipo de reporte que deseas generar</p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <a href="{{ route('reportes.gimnasio') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                <flux:icon name="building-office-2" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte del Gimnasio</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Resumen ejecutivo del negocio</p>
            </div>
        </a>

        <a href="{{ route('reportes.ventas') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                <flux:icon name="shopping-cart" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte de Ventas</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Ventas por período y método de pago</p>
            </div>
        </a>

        <a href="{{ route('reportes.matriculas') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-violet-100 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400">
                <flux:icon name="user-group" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte de Matrículas</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Membresías y clases contratadas</p>
            </div>
        </a>

        <a href="{{ route('reportes.financiero') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
                <flux:icon name="currency-dollar" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte Financiero</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Ingresos, pagos y resumen</p>
            </div>
        </a>

        <a href="{{ route('reportes.clientes') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-cyan-100 dark:bg-cyan-900/30 text-cyan-600 dark:text-cyan-400">
                <flux:icon name="users" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte de Clientes</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Clientes por estado y actividad</p>
            </div>
        </a>

        <a href="{{ route('reportes.clientes-membresia-clases') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-teal-100 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400">
                <flux:icon name="identification" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Membresía y clases activas</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Clientes con membresía/clases activas y pagos</p>
            </div>
        </a>

        <a href="{{ route('reportes.usuarios') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-900/30 text-slate-600 dark:text-slate-400">
                <flux:icon name="user-circle" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte de Usuarios</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Ventas y actividad por usuario</p>
            </div>
        </a>

        <a href="{{ route('reportes.cajas') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400">
                <flux:icon name="banknotes" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Reporte de Cajas</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Aperturas, cierres e ingresos</p>
            </div>
        </a>

        <a href="{{ route('reportes.productos-servicios') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-orange-100 dark:bg-orange-900/30 text-orange-600 dark:text-orange-400">
                <flux:icon name="cube" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Productos y Servicios</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Más vendidos y stock bajo</p>
            </div>
        </a>

        <a href="{{ route('reportes.cuentas-por-cobrar') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400">
                <flux:icon name="document-text" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Cuentas por cobrar</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Deudas y ventas a crédito</p>
            </div>
        </a>

        <a href="{{ route('reportes.cuotas-vencidas') }}" wire:navigate
            class="flex items-center gap-3 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
            <span class="flex h-12 w-12 items-center justify-center rounded-lg bg-rose-100 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400">
                <flux:icon name="currency-dollar" class="size-6" />
            </span>
            <div>
                <span class="font-semibold text-zinc-900 dark:text-zinc-100">Cuotas vencidas</span>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">Cuotas de matrícula pendientes de pago</p>
            </div>
        </a>
    </div>
</div>
