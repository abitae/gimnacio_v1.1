<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Productos y Servicios</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte de Productos y Servicios</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> Productos activos: {{ $resumen['total_productos_activos'] }} · Servicios activos: {{ $resumen['total_servicios_activos'] }} ·
        Productos con stock bajo: {{ $resumen['productos_bajo_stock'] }}
    </div>

    <p class="section-title">Más vendidos (período)</p>
    <table>
        <tr>
            <th>#</th>
            <th>Tipo</th>
            <th>Nombre</th>
            <th>Cantidad vendida</th>
            <th>Total (S/)</th>
        </tr>
        @foreach($items_mas_vendidos as $item)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $item->tipo_item ?? '-' }}</td>
                <td>{{ $item->nombre_item ?? '-' }}</td>
                <td class="text-center">{{ $item->cantidad_vendida ?? 0 }}</td>
                <td class="text-right">S/ {{ number_format($item->total ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p class="section-title" style="margin-top:12px;">Productos con stock bajo</p>
    <table>
        <tr>
            <th>#</th>
            <th>Código</th>
            <th>Nombre</th>
            <th>Stock actual</th>
            <th>Stock mínimo</th>
        </tr>
        @foreach($productos_bajo_stock as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->codigo ?? '-' }}</td>
                <td>{{ $p->nombre ?? '-' }}</td>
                <td class="text-center">{{ $p->stock_actual }}</td>
                <td class="text-center">{{ $p->stock_minimo }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
