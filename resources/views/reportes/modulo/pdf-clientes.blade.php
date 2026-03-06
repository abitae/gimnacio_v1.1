<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Clientes</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Reporte detallado de Clientes</h1>
        <p class="report-subtitle">@if(!empty($fecha_desde) && $fecha_desde !== '—') Período registro: {{ $fecha_desde }} al {{ $fecha_hasta ?? '—' }} · @endif Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong> Total: {{ $resumen['total'] }} clientes ·
        Con membresías: {{ $resumen['con_membresias'] ?? 0 }} ·
        Con pagos: {{ $resumen['con_pagos'] ?? 0 }}
        @foreach($resumen['por_estado'] ?? [] as $estado => $cant)
            · {{ $estado ?: 'Sin estado' }}: {{ $cant }}
        @endforeach
    </div>

    <p class="section-title">Detalle de clientes</p>
    <table>
        <tr>
            <th>#</th>
            <th>Tipo Doc.</th>
            <th>Nº Documento</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>Teléfono</th>
            <th>Email</th>
            <th>Estado</th>
            <th>Membresías</th>
            <th>Pagos</th>
        </tr>
        @foreach($clientes as $c)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $c->tipo_documento ?? '-' }}</td>
                <td>{{ $c->numero_documento ?? '-' }}</td>
                <td>{{ $c->nombres ?? '-' }}</td>
                <td>{{ $c->apellidos ?? '-' }}</td>
                <td>{{ $c->telefono ?? '-' }}</td>
                <td>{{ $c->email ?? '-' }}</td>
                <td>{{ $c->estado_cliente ?? '-' }}</td>
                <td class="text-center">{{ $c->cliente_membresias_count ?? 0 }}</td>
                <td class="text-center">{{ $c->pagos_count ?? 0 }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
