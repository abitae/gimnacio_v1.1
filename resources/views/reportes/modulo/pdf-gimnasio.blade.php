<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte del Gimnasio</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte ejecutivo del Gimnasio</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Ventas (período):</strong> S/ {{ number_format($resumen['ventas_total'], 2) }} ({{ $resumen['ventas_cantidad'] }} transacciones)
    </div>
    <div class="resumen-box">
        <strong>Matrículas nuevas:</strong> {{ $resumen['matriculas_nuevas'] }} · Ingresos por matrículas: S/ {{ number_format($resumen['ingresos_matriculas'], 2) }}
    </div>
    <div class="resumen-box">
        <strong>Ingresos totales (período):</strong> S/ {{ number_format($resumen['ingresos_totales'], 2) }}
    </div>
    <div class="resumen-box">
        <strong>Clientes:</strong> {{ $resumen['clientes_totales'] }} totales · {{ $resumen['clientes_activos'] }} activos
    </div>
    <div class="resumen-box">
        <strong>Membresías activas:</strong> {{ $resumen['membresias_activas'] }}
    </div>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
