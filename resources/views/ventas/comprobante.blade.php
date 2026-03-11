<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprobante {{ $venta->tipo_comprobante }} {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }}</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 800px; margin: 1rem auto; padding: 1rem; color: #18181b; }
        .no-print { margin-bottom: 1rem; }
        @media print {
            body { margin: 0; padding: 0.5rem; }
            .no-print { display: none !important; }
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 0.35rem 0.5rem; border-bottom: 1px solid #e4e4e7; }
        th { font-weight: 600; background: #f4f4f5; }
        .text-right { text-align: right; }
        .font-bold { font-weight: 700; }
        .text-lg { font-size: 1.125rem; }
        .mt-4 { margin-top: 1rem; }
        .mb-2 { margin-bottom: 0.5rem; }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()" style="padding: 0.5rem 1rem; background: #7c3aed; color: white; border: none; border-radius: 0.5rem; cursor: pointer; font-weight: 500;">
            Imprimir
        </button>
    </div>

    <h1 style="font-size: 1.25rem; margin-bottom: 0.5rem;">Comprobante de venta</h1>
    <p style="font-size: 0.875rem; color: #71717a; margin-bottom: 1rem;">
        {{ strtoupper($venta->tipo_comprobante) }} {{ $venta->serie_comprobante }}-{{ $venta->numero_comprobante }}
        · Nº venta: {{ $venta->numero_venta }}
        · Fecha: {{ $venta->fecha_venta?->format('d/m/Y H:i') }}
    </p>

    <div class="mb-2"><strong>Comprador:</strong> {{ $venta->nombre_comprador }}</div>
    <div class="mb-2"><strong>Método de pago:</strong> {{ $venta->metodo_pago }}</div>
    @if($venta->es_credito)
        <div class="mb-2"><strong>Venta a crédito:</strong> S/ {{ number_format($venta->monto_inicial ?? 0, 2) }} inicial · Vence: {{ $venta->fecha_vencimiento_deuda?->format('d/m/Y') }}</div>
    @endif

    <table class="mt-4">
        <thead>
            <tr>
                <th>Descripción</th>
                <th class="text-right">Cant.</th>
                <th class="text-right">P. unit.</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($venta->items as $item)
                <tr>
                    <td>{{ $item->nombre_item }}</td>
                    <td class="text-right">{{ $item->cantidad }}</td>
                    <td class="text-right">S/ {{ number_format($item->precio_unitario, 2) }}</td>
                    <td class="text-right">S/ {{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div style="margin-top: 1rem; text-align: right;">
        @if($venta->descuento > 0)
            <div>Descuento: -S/ {{ number_format($venta->descuento, 2) }}</div>
        @endif
        @if($venta->monto_descuento_cupon > 0)
            <div>Cupón: -S/ {{ number_format($venta->monto_descuento_cupon, 2) }}</div>
        @endif
        <div class="text-lg font-bold mt-4">Total: S/ {{ number_format($venta->total, 2) }}</div>
    </div>
</body>
</html>
