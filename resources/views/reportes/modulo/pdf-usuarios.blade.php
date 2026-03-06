<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Usuarios</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte de ventas por Usuario</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> Total ventas: S/ {{ number_format($resumen['total_ventas'], 2) }} · Total transacciones: {{ $resumen['total_transacciones'] }}
    </div>

    <p class="section-title">Ventas por usuario</p>
    <table>
        <tr>
            <th>#</th>
            <th>Usuario</th>
            <th>Email</th>
            <th>Cantidad ventas</th>
            <th>Total vendido (S/)</th>
        </tr>
        @foreach($porUsuario as $row)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $row->usuario ? $row->usuario->name : 'Usuario #' . $row->usuario_id }}</td>
                <td>{{ $row->usuario ? $row->usuario->email : '-' }}</td>
                <td class="text-center">{{ $row->cantidad }}</td>
                <td class="text-right">S/ {{ number_format($row->total_ventas ?? 0, 2) }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
