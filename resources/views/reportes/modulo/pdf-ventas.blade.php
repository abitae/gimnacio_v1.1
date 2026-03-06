<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte detallado de Ventas</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> {{ $resumen['cantidad'] }} ventas · Subtotal: S/ {{ number_format($resumen['subtotal'] ?? 0, 2) }} ·
        Descuentos: S/ {{ number_format($resumen['descuento_total'] ?? 0, 2) }} · IGV: S/ {{ number_format($resumen['igv_total'] ?? 0, 2) }} ·
        <strong>Total: S/ {{ number_format($resumen['total'], 2) }}</strong>
    </div>

    @if(!empty($resumen['por_metodo_pago']))
    <p class="section-title">Por método de pago</p>
    <table style="margin-bottom:10px;">
        <tr><th>Método</th><th class="text-center">Cantidad</th><th class="text-right">Total</th></tr>
        @foreach($resumen['por_metodo_pago'] as $metodo => $datos)
            <tr>
                <td>{{ $metodo ?: 'Sin especificar' }}</td>
                <td class="text-center">{{ is_array($datos) ? ($datos['cantidad'] ?? 0) : 0 }}</td>
                <td class="text-right">S/ {{ number_format(is_array($datos) ? ($datos['total'] ?? 0) : $datos, 2) }}</td>
            </tr>
        @endforeach
    </table>
    @endif

    <p class="section-title">Detalle de ventas</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha / Hora</th>
            <th>Nº Venta</th>
            <th>Cliente</th>
            <th>Documento</th>
            <th>Subtotal</th>
            <th>Desc.</th>
            <th>IGV</th>
            <th>Total</th>
            <th>Método pago</th>
            <th>Estado</th>
        </tr>
        @foreach($ventas as $v)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $v->fecha_venta ? $v->fecha_venta->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $v->numero_venta ?? $v->id }}</td>
                <td>{{ $v->cliente ? trim($v->cliente->nombres . ' ' . $v->cliente->apellidos) : '-' }}</td>
                <td>{{ $v->cliente ? ($v->cliente->tipo_documento ?? '') . ' ' . ($v->cliente->numero_documento ?? '') : '-' }}</td>
                <td class="text-right">S/ {{ number_format($v->subtotal ?? 0, 2) }}</td>
                <td class="text-right">S/ {{ number_format($v->descuento ?? 0, 2) }}</td>
                <td class="text-right">S/ {{ number_format($v->igv ?? 0, 2) }}</td>
                <td class="text-right"><strong>S/ {{ number_format($v->total, 2) }}</strong></td>
                <td>{{ $v->metodo_pago ?? '-' }}</td>
                <td>{{ $v->estado ?? '-' }}</td>
            </tr>
        @endforeach
    </table>

    @if($ventas->count() > 0)
    <table style="margin-top:8px;">
        <tr style="background:#f3f4f6; font-weight:bold;">
            <td colspan="5" class="text-right">Totales:</td>
            <td class="text-right">S/ {{ number_format($ventas->sum('subtotal'), 2) }}</td>
            <td class="text-right">S/ {{ number_format($ventas->sum('descuento'), 2) }}</td>
            <td class="text-right">S/ {{ number_format($ventas->sum('igv'), 2) }}</td>
            <td class="text-right">S/ {{ number_format($ventas->sum('total'), 2) }}</td>
            <td colspan="2"></td>
        </tr>
    </table>
    @endif

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
