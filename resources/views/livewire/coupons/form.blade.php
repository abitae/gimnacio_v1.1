<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4 max-w-2xl">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $couponId ? 'Editar cupón' : 'Nuevo cupón' }}</h1>
    <form wire:submit.prevent="save" class="space-y-4">
        <flux:field>
            <flux:label>Código (único)</flux:label>
            <flux:input wire:model="form.codigo" placeholder="EJ: VERANO2026" />
            @error('form.codigo') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
        <flux:field>
            <flux:label>Nombre</flux:label>
            <flux:input wire:model="form.nombre" />
            @error('form.nombre') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
        <flux:field>
            <flux:label>Descripción</flux:label>
            <flux:textarea wire:model="form.descripcion" rows="2" />
        </flux:field>
        <flux:field>
            <flux:label>Monto descuento (S/)</flux:label>
            <flux:input type="number" step="0.01" min="0" wire:model="form.valor_descuento" />
            @error('form.valor_descuento') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
        <div class="grid grid-cols-2 gap-4">
            <flux:field>
                <flux:label>Fecha inicio</flux:label>
                <flux:input type="date" wire:model="form.fecha_inicio" />
                @error('form.fecha_inicio') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
            <flux:field>
                <flux:label>Fecha vencimiento</flux:label>
                <flux:input type="date" wire:model="form.fecha_vencimiento" />
                @error('form.fecha_vencimiento') <flux:error>{{ $message }}</flux:error> @enderror
            </flux:field>
        </div>
        <flux:field>
            <flux:label>Cantidad máx. usos (vacío = ilimitado)</flux:label>
            <flux:input type="number" min="1" wire:model="form.cantidad_max_usos" placeholder="Opcional" />
            @error('form.cantidad_max_usos') <flux:error>{{ $message }}</flux:error> @enderror
        </flux:field>
        <flux:field>
            <flux:label>Aplica a</flux:label>
            <select wire:model="form.aplica_a" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="todos">Todos</option>
                <option value="pos">POS</option>
                <option value="matricula">Matrícula</option>
                <option value="membresia">Membresía</option>
                <option value="clases">Clases</option>
            </select>
        </flux:field>
        <flux:field>
            <flux:label>Estado</flux:label>
            <select wire:model="form.estado" class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-800 px-3 py-2">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
            </select>
        </flux:field>
        <div class="flex gap-2 pt-2">
            <flux:button variant="ghost" type="button" href="{{ route('cupones.index') }}" wire:navigate>Cancelar</flux:button>
            <flux:button type="submit">Guardar</flux:button>
        </div>
    </form>
</div>
