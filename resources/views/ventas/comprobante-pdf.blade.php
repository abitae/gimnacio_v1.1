<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }}</title>
    <style>
        html, body, body * {
            color: #000;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8pt;
            margin: 0;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td {
            padding: 2px 0;
            border-bottom: 1px dotted #000;
            font-size: 8pt;
            color: #000;
        }
        p, strong, span, div {
            color: #000;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .line { border-bottom: 1px dashed #000; margin: 4px 0; }
        .brand-logo { max-width: 80px; max-height: 40px; margin: 0 auto 4px; display: block; }
        .muted { font-size: 7pt; color: #000; }
    </style>
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
    <p class="text-center font-bold" style="font-size: 10pt; margin-bottom: 2px;">{{ $appBrandName ?? config('app.name') }}</p>
    <p class="text-center muted" style="margin-bottom: 6px; font-size: 8pt;">
        {{ $venta->sucursal?->nombre ?? $venta->caja?->sucursal?->nombre ?? 'Sucursal principal' }}
    </p>
    <p class="text-center font-bold" style="font-size: 9pt; margin-bottom: 4px;">TICKET DE VENTA</p>
    <p class="text-center muted" style="margin-bottom: 6px; font-size: 8pt;">
        {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }} · {{ $venta->numero_venta }} · {{ $venta->fecha_venta?->format('d/m/Y H:i') }}
    </p>
    <div class="line"></div>

    <p style="margin: 2px 0;"><strong>Estado:</strong> {{ $venta->es_credito ? 'CREDITO / '.$estadoCredito : $estadoCredito }}</p>
    <p style="margin: 2px 0;"><strong>Cliente:</strong> {{ $venta->nombre_comprador }}</p>
    @if($venta->cliente)
        <p style="margin: 2px 0;"><strong>Codigo:</strong> {{ $venta->cliente->codigo ?? '-' }} · <strong>{{ $venta->cliente->tipo_documento ?? 'Doc.' }}:</strong> {{ $venta->cliente->numero_documento ?? '-' }} · <strong>Tel.:</strong> {{ $venta->cliente->telefono ?? '-' }}</p>
    @endif
    <p style="margin: 2px 0;"><strong>Caja:</strong> #{{ $venta->caja_id ?? '-' }}</p>
    <p style="margin: 2px 0;"><strong>Atendió:</strong> {{ $venta->usuario?->name ?? '—' }}</p>
    <p style="margin: 2px 0;"><strong>Pago:</strong> {{ $venta->paymentMethod?->nombre ?? $venta->metodo_pago }}</p>
    <p style="margin: 2px 0;"><strong>Pago detalle:</strong> {{ $venta->metodosPagoResumen() }}</p>
    @if($venta->numero_operacion)
        <p style="margin: 2px 0;"><strong>N° oper.:</strong> {{ $venta->numero_operacion }}</p>
    @endif
    @if($venta->es_credito)
        <p style="margin: 2px 0;"><strong>Crédito:</strong> Inicial S/ {{ number_format((float) ($venta->monto_inicial ?? 0), 2) }} · Vence {{ $venta->fecha_vencimiento_deuda?->format('d/m/Y') }}</p>
    @endif
    @if($venta->es_credito)
        <p style="margin: 2px 0;"><strong>Saldo credito:</strong> S/ {{ number_format($saldoPendiente, 2) }}</p>
    @endif
    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Detalle</th>
                <th class="text-right">Cant</th>
                <th class="text-right">P.U</th>
                <th class="text-right">Subt.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->items as $item)
                <tr>
                    <td style="word-break: break-word;">{{ $item->nombre_item }}</td>
                    <td class="text-right">{{ $item->cantidad }}</td>
                    <td class="text-right">S/ {{ number_format((float) $item->precio_unitario, 2) }}</td>
                    <td class="text-right">S/ {{ number_format((float) $item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>
    <p class="text-right" style="margin: 1px 0;">Subtotal: S/ {{ number_format((float) $venta->subtotal, 2) }}</p>
    @if((float) $venta->descuento > 0)
        <p class="text-right" style="margin: 1px 0;">Descuento: -S/ {{ number_format((float) $venta->descuento, 2) }}</p>
    @endif
    <p class="text-right font-bold" style="font-size: 10pt; margin-top: 4px;">TOTAL: S/ {{ number_format((float) $venta->total, 2) }}</p>
    <p class="text-right" style="margin: 1px 0;">Pagado: S/ {{ number_format($montoPagado, 2) }}</p>
    <p class="text-right" style="margin: 1px 0;">Saldo: S/ {{ number_format($saldoPendiente, 2) }}</p>
</body>
</html>
