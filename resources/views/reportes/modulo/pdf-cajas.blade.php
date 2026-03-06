<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Cajas</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte detallado de Cajas</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> {{ $resumen['cantidad'] }} cajas ({{ $resumen['abiertas'] }} abiertas, {{ $resumen['cerradas'] }} cerradas) ·
        Total ingresos: S/ {{ number_format($resumen['total_ingresos'], 2) }} ·
        Total salidas: S/ {{ number_format($resumen['total_salidas'], 2) }}
    </div>

    <p class="section-title">Detalle de cajas</p>
    <table>
        <tr>
            <th>#</th>
            <th>Usuario</th>
            <th>Fecha apertura</th>
            <th>Fecha cierre</th>
            <th>Saldo inicial</th>
            <th>Saldo final</th>
            <th>Estado</th>
        </tr>
        @foreach($cajas as $c)
            <tr>
                <td>{{ $c->id }}</td>
                <td>{{ $c->usuario ? $c->usuario->name : '-' }}</td>
                <td>{{ $c->fecha_apertura ? $c->fecha_apertura->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $c->fecha_cierre ? $c->fecha_cierre->format('d/m/Y H:i') : '-' }}</td>
                <td class="text-right">S/ {{ number_format($c->saldo_inicial, 2) }}</td>
                <td class="text-right">S/ {{ number_format($c->saldo_final ?? 0, 2) }}</td>
                <td>{{ $c->estado }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
