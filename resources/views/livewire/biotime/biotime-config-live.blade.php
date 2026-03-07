<div class="space-y-6">
    <!-- Header -->
    <div class="rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 p-6 shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white/20 backdrop-blur-sm">
                    <flux:icon name="cog-6-tooth" class="h-6 w-6 text-white" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-white">Configuración BioTime</h1>
                    <p class="text-sm text-white/90">Conexión con ZKTeco / BioTime (personnel, iclock, att)</p>
                </div>
            </div>
            <a href="{{ route('biotime.index') }}" wire:navigate
                class="rounded-lg bg-white/10 px-4 py-2 text-sm font-medium text-white hover:bg-white/20">
                Volver al dashboard
            </a>
        </div>
    </div>

    <!-- Aviso: precauciones firewall (nube → red local) -->
    <div role="status" aria-live="polite"
        class="flex gap-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 shadow-sm dark:border-amber-800 dark:bg-amber-900/20 dark:text-amber-200">
        <flux:icon name="shield-exclamation" class="h-6 w-6 shrink-0" />
        <div class="space-y-1">
            <p class="font-semibold">Conexión desde la nube a red local</p>
            <p class="text-sm">
                Si esta aplicación está en la nube y BioTime en su red local (reenvío de puertos o túnel), la nube debe contar con el permiso correspondiente para acceder: configure el firewall de la red local para autorizar únicamente las IPs de este servidor (o use VPN). No exponga el puerto de BioTime a internet.
            </p>
        </div>
    </div>

    <!-- Form -->
    <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
        <form wire:submit="save" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div class="md:col-span-2">
                    <flux:field>
                        <flux:label>URL base</flux:label>
                        <flux:input type="url" wire:model="base_url" placeholder="https://zkeco.example.com:8097"
                            class="w-full" />
                        <flux:error name="base_url" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Usuario</flux:label>
                        <flux:input type="text" wire:model="username" placeholder="admin" class="w-full" />
                        <flux:error name="username" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Contraseña</flux:label>
                        <flux:input type="password" wire:model="password" placeholder="Dejar en blanco para no cambiar"
                            class="w-full" />
                        <flux:error name="password" />
                    </flux:field>
                </div>
                <div>
                    <flux:field>
                        <flux:label>Tipo de autenticación</flux:label>
                        <select wire:model="auth_type"
                            class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-zinc-900 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100">
                            <option value="jwt">JWT</option>
                            <option value="token">Token (General)</option>
                        </select>
                    </flux:field>
                </div>
                <div class="flex items-end">
                    <flux:checkbox wire:model="enabled" label="Integración habilitada" />
                </div>
            </div>

            @if ($last_tested_at)
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Última prueba de conexión: {{ $last_tested_at }}
                </p>
            @endif

            <div class="flex flex-wrap gap-3">
                <flux:button type="submit" color="purple" variant="primary" wire:loading.attr="disabled"
                    wire:target="save">
                    <span class="inline-flex items-center gap-1.5">
                        <flux:icon name="arrow-path" class="size-4 shrink-0 animate-spin" wire:loading wire:target="save" />
                        <span wire:loading.remove wire:target="save">Guardar</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </span>
                </flux:button>
                <flux:button type="button" variant="ghost" wire:click="testConnection"
                    wire:loading.attr="disabled" wire:target="testConnection">
                    <span wire:loading.remove wire:target="testConnection">Probar conexión</span>
                    <span wire:loading wire:target="testConnection">Probando...</span>
                </flux:button>
            </div>
        </form>

        @if ($testMessage !== '')
            <div class="mt-4 rounded-lg p-4 {{ $testSuccess ? 'bg-green-50 text-green-800 dark:bg-green-900/20 dark:text-green-400' : 'bg-red-50 text-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
                {{ $testMessage }}
            </div>
        @endif
    </div>
</div>
