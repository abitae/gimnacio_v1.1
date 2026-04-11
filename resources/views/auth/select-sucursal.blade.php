<x-layouts.auth>
    <div class="flex flex-col gap-8 rounded-lg border border-gray-500 p-4">
        <x-auth-header
            title="Selecciona la sucursal"
            description="Debes elegir la sucursal con la que deseas trabajar en esta sesión."
        />

        <form method="POST" action="{{ route('sucursal-context.store') }}" class="flex flex-col gap-6">
            @csrf

            <div class="flex flex-col gap-2">
                <label for="sucursal_id" class="text-sm font-medium text-zinc-800 dark:text-zinc-100">Sucursal</label>
                <select
                    id="sucursal_id"
                    name="sucursal_id"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-500 focus:outline-none focus:ring-1 focus:ring-zinc-500 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100"
                    required
                >
                    <option value="">Seleccionar sucursal</option>
                    @foreach ($sucursales as $sucursal)
                        <option value="{{ $sucursal->id }}" @selected(old('sucursal_id', $activeSucursalId) == $sucursal->id)>
                            {{ $sucursal->empresa?->nombre }} - {{ $sucursal->nombre }}
                        </option>
                    @endforeach
                </select>
                @error('sucursal_id')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <flux:button variant="primary" type="submit" class="w-full">
                Continuar
            </flux:button>
        </form>
    </div>
</x-layouts.auth>
