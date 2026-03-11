<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #000; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 2px 0; border-bottom: 1px dotted #999; font-size: 8pt; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .line { border-bottom: 1px dashed #000; margin: 4px 0; }
    </style>
</head>
<body>
    <p class="text-center font-bold" style="font-size: 10pt; margin-bottom: 4px;">TICKET DE VENTA</p>
    <p class="text-center" style="font-size: 7pt; margin-bottom: 6px;">
        {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }} &nbsp;|&nbsp; {{ $venta->numero_venta }} &nbsp;|&nbsp; {{ $venta->fecha_venta?->format('d/m/Y H:i') }}
    </p>
    <div class="line"></div>

    <p style="margin: 2px 0;"><strong>Cliente:</strong> {{ $venta->nombre_comprador }}</p>
    <p style="margin: 2px 0;"><strong>Pago:</strong> {{ $venta->metodo_pago }}</p>
    @if($venta->es_credito)
        <p style="margin: 2px 0;"><strong>Cr&eacute;dito:</strong> S/ {{ number_format($venta->monto_inicial ?? 0, 2) }} &nbsp; Vence: {{ $venta->fecha_vencimiento_deuda?->format('d/m/Y') }}</p>
    @endif
    <div class="line"></div>

    <table>
        <thead>
            <tr>
                <th style="text-align: left;">Desc.</th>
                <th class="text-right">Cant</th>
                <th class="text-right">P.U</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->items as $item)
                <tr>
                    <td style="word-break: break-word;">{{ $item->nombre_item }}</td>
                    <td class="text-right">{{ $item->cantidad }}</td>
                    <td class="text-right">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="line"></div>
    @if($venta->descuento > 0)
        <p class="text-right" style="margin: 1px 0;">Descuento: -S/ {{ number_format($venta->descuento, 2) }}</p>
    @endif
    @if($venta->monto_descuento_cupon > 0)
        <p class="text-right" style="margin: 1px 0;">Cup&oacute;n: -S/ {{ number_format($venta->monto_descuento_cupon, 2) }}</p>
    @endif
    <p class="text-right font-bold" style="font-size: 10pt; margin-top: 4px;">TOTAL: S/ {{ number_format($venta->total, 2) }}</p>
    <div class="line"></div>
</body>
</html>
