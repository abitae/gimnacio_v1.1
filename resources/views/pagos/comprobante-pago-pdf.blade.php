@php
    $clienteNombre = $pago->cliente ? trim($pago->cliente->nombres.' '.$pago->cliente->apellidos) : '—';
    $concepto = 'Cobro';
    if ($pago->clienteMatricula) {
        $concepto = ucfirst((string) $pago->clienteMatricula->tipo).' — '.$pago->clienteMatricula->nombre;
    } elseif ($pago->clienteMembresia) {
        $concepto = 'Membresia — '.($pago->clienteMembresia->membresia->nombre ?? 'N/A');
    } elseif ($pago->clientDebt?->venta) {
        $concepto = 'Venta credito — '.$pago->clientDebt->venta->numero_venta;
    }
    $simbolo = $pago->moneda === 'USD' ? '$' : 'S/';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $pago->comprobante_numero ?? $pago->id }}</title>
    <x-tickets.thermal-pdf-styles />
</head>
<body>
    @if(!empty($appBrandLogoUrl))
        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name') }}" class="brand-logo">
    @endif

    <p class="header-title">{{ $appBrandName ?? config('app.name') }}</p>
    <p class="header-meta">{{ $pago->sucursal?->nombre ?? 'Sucursal principal' }}</p>
    <p class="header-subtitle">TICKET DE COBRO</p>
    <p class="header-meta">
        {{ strtoupper((string) ($pago->comprobante_tipo ?? 'ticket')) }} {{ $pago->comprobante_numero ?? '—' }}<br>
        Pago #{{ $pago->id }}<br>
        {{ $pago->fechaHoraPago()->format('d/m/Y H:i') }}
    </p>
    <div class="line"></div>

    <table class="kv">
        <tr>
            <td class="label">Cliente</td>
            <td class="value">{{ $clienteNombre }}</td>
        </tr>
        @if($pago->cliente)
            @if(filled($pago->cliente->codigo))
                <tr>
                    <td class="label">Codigo</td>
                    <td class="value">{{ $pago->cliente->codigo }}</td>
                </tr>
            @endif
            @if(filled($pago->cliente->numero_documento))
                <tr>
                    <td class="label">{{ $pago->cliente->tipo_documento ?? 'Doc.' }}</td>
                    <td class="value">{{ $pago->cliente->numero_documento }}</td>
                </tr>
            @endif
        @endif
        <tr>
            <td class="label">Concepto</td>
            <td class="value">{{ $concepto }}</td>
        </tr>
        @if($pago->detalles->isNotEmpty())
            @foreach($pago->detalles as $detalle)
                <tr>
                    <td class="label">{{ $pago->detalles->count() > 1 ? 'Metodo '.($loop->index + 1) : 'Metodo' }}</td>
                    <td class="value">
                        {{ $detalle->paymentMethod?->nombre ?? $detalle->metodo_pago }} —
                        {{ $simbolo }} {{ number_format((float) $detalle->monto, 2) }}
                    </td>
                </tr>
                @if($detalle->numero_operacion)
                    <tr><td class="label">N oper.</td><td class="value">{{ $detalle->numero_operacion }}</td></tr>
                @endif
                @if($detalle->entidad_financiera)
                    <tr><td class="label">Entidad</td><td class="value">{{ $detalle->entidad_financiera }}</td></tr>
                @endif
            @endforeach
        @else
            <tr>
                <td class="label">Metodo</td>
                <td class="value">{{ $pago->paymentMethod?->nombre ?? $pago->metodo_pago }}</td>
            </tr>
            @if($pago->numero_operacion)
                <tr><td class="label">N oper.</td><td class="value">{{ $pago->numero_operacion }}</td></tr>
            @endif
            @if($pago->entidad_financiera)
                <tr><td class="label">Entidad</td><td class="value">{{ $pago->entidad_financiera }}</td></tr>
            @endif
        @endif
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="text-right">{{ $simbolo }} {{ number_format((float) $pago->monto, 2) }}</td>
        </tr>
        @if($pago->es_pago_parcial && (float) $pago->saldo_pendiente > 0)
            <tr>
                <td>Saldo pend.</td>
                <td class="text-right">{{ $simbolo }} {{ number_format((float) $pago->saldo_pendiente, 2) }}</td>
            </tr>
        @endif
    </table>

    <div class="line"></div>
    <p class="footer-note">Registrado por: {{ $pago->registradoPor?->name ?? '—' }}</p>
</body>
</html>
