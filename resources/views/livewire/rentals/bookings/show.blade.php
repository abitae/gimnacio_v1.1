<div class="space-y-3 border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-zinc-900 dark:text-zinc-100">Reserva</h1>
        <flux:button variant="ghost" size="xs" href="{{ route('rentals.calendar.index', ['fecha' => $rental->fecha->format('Y-m-d')]) }}" wire:navigate>Volver</flux:button>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><span class="text-zinc-500">Espacio</span><p class="font-medium">{{ $rental->rentableSpace->nombre }}</p></div>
        <div><span class="text-zinc-500">Fecha</span><p class="font-medium">{{ $rental->fecha->format('d/m/Y') }}</p></div>
        <div><span class="text-zinc-500">Hora</span><p class="font-medium">{{ \Carbon\Carbon::parse($rental->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($rental->hora_fin)->format('H:i') }}</p></div>
        <div><span class="text-zinc-500">Estado</span><p class="font-medium">{{ \App\Models\Core\Rental::ESTADOS[$rental->estado] ?? $rental->estado }}</p></div>
    </div>
    <p class="text-sm"><span class="text-zinc-500">Cliente:</span> {{ $rental->cliente ? $rental->cliente->nombres . ' ' . $rental->cliente->apellidos : ($rental->nombre_externo ?? '—') }}</p>
    <p class="text-sm"><span class="text-zinc-500">Precio:</span> S/ {{ number_format($rental->precio, 2) }}</p>
    @if($rental->payments->count())
    <p class="text-sm font-medium">Pagos</p>
    <ul class="text-sm list-disc pl-4">
        @foreach($rental->payments as $p)
            <li>S/ {{ number_format($p->monto, 2) }} — {{ $p->fecha_pago->format('d/m/Y') }}</li>
        @endforeach
    </ul>
    @endif
</div>
