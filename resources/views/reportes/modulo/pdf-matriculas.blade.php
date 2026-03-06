<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Matrículas</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte detallado de Matrículas</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> {{ $resumen['cantidad'] }} matrículas ({{ $resumen['membresias'] }} membresías, {{ $resumen['clases'] }} clases) ·
        Descuentos totales: S/ {{ number_format($resumen['descuentos_total'] ?? 0, 2) }} ·
        <strong>Ingresos: S/ {{ number_format($resumen['ingresos'], 2) }}</strong>
    </div>

    <p class="section-title">Detalle de matrículas</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha inicio</th>
            <th>Cliente</th>
            <th>Documento</th>
            <th>Tipo</th>
            <th>Producto / Servicio</th>
            <th>Precio lista</th>
            <th>Desc. monto</th>
            <th>Precio final</th>
            <th>Estado</th>
            <th>Canal venta</th>
            <th>Asesor</th>
        </tr>
        @foreach($matriculas as $m)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $m->fecha_inicio ? $m->fecha_inicio->format('d/m/Y') : '-' }}</td>
                <td>{{ $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '-' }}</td>
                <td>{{ $m->cliente ? ($m->cliente->tipo_documento ?? '') . ' ' . ($m->cliente->numero_documento ?? '') : '-' }}</td>
                <td>{{ $m->tipo ?? '-' }}</td>
                <td>{{ $m->nombre }}</td>
                <td class="text-right">S/ {{ number_format($m->precio_lista ?? 0, 2) }}</td>
                <td class="text-right">S/ {{ number_format($m->descuento_monto ?? 0, 2) }}</td>
                <td class="text-right"><strong>S/ {{ number_format($m->precio_final ?? 0, 2) }}</strong></td>
                <td>{{ $m->estado ?? '-' }}</td>
                <td>{{ $m->canal_venta ?? '-' }}</td>
                <td>{{ $m->asesor ? $m->asesor->name : '-' }}</td>
            </tr>
        @endforeach
    </table>

    @if($matriculas->count() > 0)
    <table style="margin-top:8px;">
        <tr style="background:#f3f4f6; font-weight:bold;">
            <td colspan="8" class="text-right">Total ingresos:</td>
            <td class="text-right">S/ {{ number_format($matriculas->sum('precio_final'), 2) }}</td>
            <td colspan="3"></td>
        </tr>
    </table>
    @endif

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
