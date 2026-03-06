<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte Financiero detallado</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> Total pagos: S/ {{ number_format($resumen['total_pagos'], 2) }} ({{ $resumen['cantidad_pagos'] }} transacciones) ·
        Total ventas: S/ {{ number_format($resumen['total_ventas'], 2) }} ({{ $resumen['cantidad_ventas'] }} ventas) ·
        <strong>Ingresos totales: S/ {{ number_format($resumen['ingresos_totales'], 2) }}</strong>
    </div>

    <p class="section-title">Últimos pagos</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha / Hora</th>
            <th>Cliente</th>
            <th>Monto</th>
            <th>Moneda</th>
            <th>Método pago</th>
            <th>Comprobante</th>
        </tr>
        @foreach($pagos as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->fecha_pago ? $p->fecha_pago->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-' }}</td>
                <td class="text-right">S/ {{ number_format($p->monto, 2) }}</td>
                <td>{{ $p->moneda ?? 'PEN' }}</td>
                <td>{{ $p->metodo_pago ?? '-' }}</td>
                <td>{{ ($p->comprobante_tipo ?? '') . ' ' . ($p->comprobante_numero ?? '') }}</td>
            </tr>
        @endforeach
    </table>

    <p class="section-title" style="margin-top:12px;">Últimas ventas</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Método pago</th>
        </tr>
        @foreach($ventas as $v)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $v->fecha_venta ? $v->fecha_venta->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $v->cliente ? trim($v->cliente->nombres . ' ' . $v->cliente->apellidos) : '-' }}</td>
                <td class="text-right">S/ {{ number_format($v->total, 2) }}</td>
                <td>{{ $v->metodo_pago ?? '-' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
