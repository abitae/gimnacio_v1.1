<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Financiero</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    @php
        $matriz = $resumen['matriz_tipo_metodo'] ?? [];
        $tiposMatriz = $matriz['tipos'] ?? [];
        $metodosMatriz = $matriz['metodos'] ?? [];
        $celdasMatriz = $matriz['celdas'] ?? [];
    @endphp
    <div class="report-header">
        <h1 class="report-title">Reporte Financiero detallado</h1>
        <p class="report-subtitle">Período: {{ $fecha_desde }} al {{ $fecha_hasta }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong>
        Cobros: S/ {{ number_format($resumen['total_pagos'], 2) }} ({{ $resumen['cantidad_pagos'] }}) ·
        Ventas cobradas: S/ {{ number_format($resumen['ventas_cobradas'] ?? 0, 2) }} ({{ $resumen['cantidad_ventas'] }}) ·
        Saldo crédito: S/ {{ number_format($resumen['saldo_credito'] ?? 0, 2) }} ·
        <strong>Ingresos reales: S/ {{ number_format($resumen['ingresos_totales'], 2) }}</strong>
    </div>

    <p class="section-title">Totales por tipo y método de pago</p>
    <table>
        <tr>
            <th>Tipo</th>
            @foreach ($metodosMatriz as $metodo)
                <th class="text-right">{{ $metodo }}</th>
            @endforeach
            <th class="text-right">Total</th>
        </tr>
        @forelse ($tiposMatriz as $tipo)
            <tr>
                <td>{{ $tipo }}</td>
                @foreach ($metodosMatriz as $metodo)
                    @php $celda = $celdasMatriz[$tipo][$metodo] ?? null; @endphp
                    <td class="text-right">{{ $celda ? 'S/ '.number_format((float) $celda['total'], 2) : '—' }}</td>
                @endforeach
                <td class="text-right"><strong>S/ {{ number_format((float) ($matriz['totales_tipo'][$tipo] ?? 0), 2) }}</strong></td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ max(2, count($metodosMatriz) + 2) }}">Sin ingresos reales en el período.</td>
            </tr>
        @endforelse
        @if (count($tiposMatriz) > 0)
            <tr>
                <td><strong>Total</strong></td>
                @foreach ($metodosMatriz as $metodo)
                    <td class="text-right"><strong>S/ {{ number_format((float) ($matriz['totales_metodo'][$metodo] ?? 0), 2) }}</strong></td>
                @endforeach
                <td class="text-right"><strong>S/ {{ number_format((float) ($matriz['total_general'] ?? 0), 2) }}</strong></td>
            </tr>
        @endif
    </table>

    <p class="section-title" style="margin-top:12px;">Pagos / cobros</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha / Hora</th>
            <th>Cliente</th>
            <th>Tipo</th>
            <th>Monto</th>
            <th>Método pago</th>
            <th>Comprobante</th>
        </tr>
        @foreach($pagos as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->fecha_pago ? $p->fecha_pago->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-' }}</td>
                <td>{{ $p->etiquetaOrigen() }}</td>
                <td class="text-right">S/ {{ number_format($p->monto, 2) }}</td>
                <td>{{ method_exists($p, 'metodosPagoResumen') ? $p->metodosPagoResumen() : ($p->metodo_pago ?? '-') }}</td>
                <td>{{ trim(($p->comprobante_tipo ?? '').' '.($p->comprobante_numero ?? '')) ?: '—' }}</td>
            </tr>
        @endforeach
    </table>

    <p class="section-title" style="margin-top:12px;">Ventas POS</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Nº</th>
            <th>Cliente</th>
            <th>Total</th>
            <th>Método pago</th>
            <th>Estado</th>
        </tr>
        @foreach($ventas as $v)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $v->fecha_venta ? $v->fecha_venta->format('d/m/Y H:i') : '-' }}</td>
                <td>{{ $v->numero_venta ?? $v->id }}</td>
                <td>{{ $v->cliente ? trim($v->cliente->nombres . ' ' . $v->cliente->apellidos) : ($v->nombre_comprador ?? '-') }}</td>
                <td class="text-right">S/ {{ number_format($v->total, 2) }}</td>
                <td>{{ method_exists($v, 'metodosPagoResumen') ? $v->metodosPagoResumen() : ($v->metodo_pago ?? '-') }}</td>
                <td>{{ $v->estado ?? '-' }}{{ ! empty($v->es_credito) ? ' · crédito' : '' }}</td>
            </tr>
        @endforeach
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
