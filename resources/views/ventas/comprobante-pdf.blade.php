<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }}</title>
    <x-tickets.thermal-pdf-styles />
</head>
<body>
    @php
        $montoPagado = $venta->montoPagadoInicial();
        $saldoPendiente = $venta->saldoPendienteVenta();
        $estadoCredito = $venta->es_credito ? ($saldoPendiente > 0 ? 'PENDIENTE' : 'PAGADO') : 'PAGADO';
    @endphp

    @if(!empty($appBrandLogoUrl))
        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name') }}" class="brand-logo">
    @endif

    <p class="header-title">{{ $appBrandName ?? config('app.name') }}</p>
    <p class="header-meta">{{ $venta->sucursal?->nombre ?? $venta->caja?->sucursal?->nombre ?? 'Sucursal principal' }}</p>
    <p class="header-subtitle">TICKET DE VENTA</p>
    <p class="header-meta">
        {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }}<br>
        {{ $venta->numero_venta }}<br>
        {{ $venta->fecha_venta?->format('d/m/Y H:i') }}
    </p>
    <div class="line"></div>

    <table class="kv">
        <tr>
            <td class="label">Estado</td>
            <td class="value">{{ $venta->es_credito ? 'CREDITO / '.$estadoCredito : $estadoCredito }}</td>
        </tr>
        <tr>
            <td class="label">Cliente</td>
            <td class="value">{{ $venta->nombre_comprador }}</td>
        </tr>
        @if($venta->cliente)
            @if(filled($venta->cliente->codigo))
                <tr>
                    <td class="label">Codigo</td>
                    <td class="value">{{ $venta->cliente->codigo }}</td>
                </tr>
            @endif
            <tr>
                <td class="label">{{ $venta->cliente->tipo_documento ?? 'Doc.' }}</td>
                <td class="value">{{ $venta->cliente->numero_documento ?? '-' }}</td>
            </tr>
            @if(filled($venta->cliente->telefono))
                <tr>
                    <td class="label">Telefono</td>
                    <td class="value">{{ $venta->cliente->telefono }}</td>
                </tr>
            @endif
        @endif
        <tr>
            <td class="label">Caja</td>
            <td class="value">#{{ $venta->caja_id ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">Atendio</td>
            <td class="value">{{ $venta->usuario?->name ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Pago</td>
            <td class="value">{{ $venta->paymentMethod?->nombre ?? $venta->metodo_pago }}</td>
        </tr>
        <tr>
            <td class="label">Detalle pago</td>
            <td class="value">{{ $venta->metodosPagoResumen() }}</td>
        </tr>
        @if($venta->numero_operacion)
            <tr>
                <td class="label">N oper.</td>
                <td class="value">{{ $venta->numero_operacion }}</td>
            </tr>
        @endif
        @if($venta->es_credito)
            <tr>
                <td class="label">Credito</td>
                <td class="value">
                    Inicial S/ {{ number_format((float) ($venta->monto_inicial ?? 0), 2) }}<br>
                    Vence {{ $venta->fecha_vencimiento_deuda?->format('d/m/Y') ?? '—' }}
                </td>
            </tr>
            <tr>
                <td class="label">Saldo</td>
                <td class="value">S/ {{ number_format($saldoPendiente, 2) }}</td>
            </tr>
        @endif
    </table>

    <div class="line"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th class="col-detail">Detalle</th>
                <th class="col-qty">Cant</th>
                <th class="col-price">P.U</th>
                <th class="col-sub">Subt.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->items as $item)
                <tr>
                    <td class="col-detail">
                        <div class="item-name">{{ $item->nombre_item }}</div>
                    </td>
                    <td class="col-qty text-right">{{ $item->cantidad }}</td>
                    <td class="col-price text-right">{{ number_format((float) $item->precio_unitario, 2) }}</td>
                    <td class="col-sub text-right">S/ {{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>

    <table class="totals">
        <tr>
            <td>Subtotal</td>
            <td class="text-right">S/ {{ number_format((float) $venta->subtotal, 2) }}</td>
        </tr>
        @if((float) $venta->descuento > 0)
            <tr>
                <td>Descuento</td>
                <td class="text-right">-S/ {{ number_format((float) $venta->descuento, 2) }}</td>
            </tr>
        @endif
        <tr class="total-row">
            <td>TOTAL</td>
            <td class="text-right">S/ {{ number_format((float) $venta->total, 2) }}</td>
        </tr>
        <tr>
            <td>Pagado</td>
            <td class="text-right">S/ {{ number_format($montoPagado, 2) }}</td>
        </tr>
        <tr>
            <td>Saldo pend.</td>
            <td class="text-right">S/ {{ number_format($saldoPendiente, 2) }}</td>
        </tr>
    </table>
</body>
</html>
