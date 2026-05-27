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
        Productos con stock bajo: {{ $resumen['productos_bajo_stock'] }} - Productos vendidos: {{ $resumen['productos_vendidos'] ?? 0 }} -
        Total productos vendidos: S/ {{ number_format($resumen['total_productos_vendidos'] ?? 0, 2) }}
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

    <p class="section-title" style="margin-top:12px;">Productos vendidos por caja</p>
    <table>
        <tr>
            <th>#</th>
            <th>Caja</th>
            <th>Usuario caja</th>
            <th>Ventas</th>
            <th>Cantidad</th>
            <th>Total (S/)</th>
        </tr>
        @foreach(($productos_por_caja ?? collect()) as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['caja'] ?? '-' }}</td>
                <td>{{ $row['usuario_caja'] ?? '-' }}</td>
                <td class="text-center">{{ $row['ventas_count'] ?? 0 }}</td>
                <td class="text-center">{{ $row['cantidad_productos'] ?? 0 }}</td>
                <td class="text-right">S/ {{ number_format($row['total'] ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p class="section-title" style="margin-top:12px;">Productos vendidos por usuario</p>
    <table>
        <tr>
            <th>#</th>
            <th>Usuario</th>
            <th>Ventas</th>
            <th>Cantidad</th>
            <th>Total (S/)</th>
        </tr>
        @foreach(($productos_por_usuario ?? collect()) as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['usuario'] ?? '-' }}</td>
                <td class="text-center">{{ $row['ventas_count'] ?? 0 }}</td>
                <td class="text-center">{{ $row['cantidad_productos'] ?? 0 }}</td>
                <td class="text-right">S/ {{ number_format($row['total'] ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <p class="section-title" style="margin-top:12px;">Detalle de productos vendidos</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Caja</th>
            <th>Vendedor</th>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Subtotal (S/)</th>
            <th>Comprobante</th>
        </tr>
        @foreach(($detalle_productos_vendidos ?? collect()) as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row['fecha']?->format('d/m/Y H:i') }}</td>
                <td>{{ $row['caja'] ?? '-' }}</td>
                <td>{{ $row['vendedor'] ?? '-' }}</td>
                <td>{{ $row['producto'] ?? '-' }}</td>
                <td class="text-center">{{ $row['cantidad'] ?? 0 }}</td>
                <td class="text-right">S/ {{ number_format($row['subtotal'] ?? 0, 2) }}</td>
                <td>{{ $row['comprobante'] ?: ($row['numero_venta'] ?? '-') }}</td>
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
