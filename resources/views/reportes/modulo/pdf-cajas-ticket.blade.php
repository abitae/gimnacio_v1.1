<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de Caja</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 9px;
            color: #111827;
            margin: 0;
        }
        .ticket {
            width: 100%;
        }
        .center {
            text-align: center;
        }
        .muted {
            color: #6b7280;
        }
        .title {
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 2px;
        }
        .section {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #9ca3af;
        }
        .row {
            width: 100%;
            border-collapse: collapse;
        }
        .row td {
            padding: 1px 0;
            vertical-align: top;
        }
        .right {
            text-align: right;
        }
        .strong {
            font-weight: 700;
        }
        .item {
            margin-bottom: 5px;
            padding-bottom: 5px;
            border-bottom: 1px dotted #d1d5db;
        }
        .totals td {
            padding: 2px 0;
        }
    </style>
</head>
<body>
    @php
        $caja = $cajas->first();
        $movimientos = collect($detalle_movimientos ?? [])->values();
    @endphp

    <div class="ticket">
        <div class="center">
            <div class="title">{{ config('app.name') }}</div>
            <div class="strong">Reporte de caja</div>
            <div class="muted">Generado {{ now()->format('d/m/Y H:i') }}</div>
        </div>

        <div class="section">
            <table class="row">
                <tr><td>Caja</td><td class="right strong">#{{ $caja?->id ?? '—' }}</td></tr>
                <tr><td>Usuario</td><td class="right">{{ $caja?->usuario?->name ?? '—' }}</td></tr>
                <tr><td>Sucursal</td><td class="right">{{ $caja?->sucursal?->nombre ?? '—' }}</td></tr>
                <tr><td>Estado</td><td class="right">{{ ucfirst($caja?->estado ?? '—') }}</td></tr>
                <tr><td>Apertura</td><td class="right">{{ $caja?->fecha_apertura?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                <tr><td>Cierre</td><td class="right">{{ $caja?->fecha_cierre?->format('d/m/Y H:i') ?? '—' }}</td></tr>
            </table>
        </div>

        <div class="section">
            <table class="row totals">
                <tr><td>Saldo inicial</td><td class="right">S/ {{ number_format((float) ($caja?->saldo_inicial ?? 0), 2) }}</td></tr>
                <tr><td>Ingresos</td><td class="right">S/ {{ number_format((float) ($resumen['total_ingresos'] ?? 0), 2) }}</td></tr>
                <tr><td>Salidas</td><td class="right">S/ {{ number_format((float) ($resumen['total_salidas'] ?? 0), 2) }}</td></tr>
                <tr><td>Total vendido</td><td class="right">S/ {{ number_format((float) ($resumen['total_vendido'] ?? 0), 2) }}</td></tr>
                <tr><td class="strong">Saldo final</td><td class="right strong">S/ {{ number_format((float) ($caja?->saldo_final ?? 0), 2) }}</td></tr>
                <tr><td>Contado</td><td class="right">S/ {{ number_format((float) ($caja?->saldo_contado_cierre ?? 0), 2) }}</td></tr>
                <tr><td>Diferencia</td><td class="right">S/ {{ number_format((float) ($caja?->diferencia_cierre ?? 0), 2) }}</td></tr>
            </table>
        </div>

        @if (! empty($resumen['por_metodo_pago']))
            <div class="section">
                <div class="strong">Totales por método</div>
                <table class="row totals">
                    @foreach ($resumen['por_metodo_pago'] as $metodo => $total)
                        <tr>
                            <td>{{ strtoupper(str_replace('_', ' ', (string) $metodo)) }}</td>
                            <td class="right">S/ {{ number_format((float) $total, 2) }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif

        <div class="section">
            <div class="strong">Movimientos</div>
            @forelse ($movimientos as $movimiento)
                <div class="item">
                    <table class="row">
                        <tr>
                            <td class="strong">{{ $movimiento['concepto'] ?? 'Movimiento' }}</td>
                            <td class="right strong">
                                {{ ($movimiento['tipo'] ?? 'entrada') === 'entrada' ? '+' : '-' }}
                                S/ {{ number_format((float) ($movimiento['monto'] ?? 0), 2) }}
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $movimiento['fecha'] ? $movimiento['fecha']->format('d/m H:i') : '—' }}</td>
                            <td class="right">{{ $movimiento['metodo_pago'] ?: '—' }}</td>
                        </tr>
                        <tr>
                            <td>{{ $movimiento['usuario'] ?: '—' }}</td>
                            <td class="right">{{ $movimiento['referencia_label'] ?: '—' }}</td>
                        </tr>
                    </table>
                </div>
            @empty
                <div class="muted">Sin movimientos registrados.</div>
            @endforelse
        </div>

        <div class="section center muted">
            Ticket generado por {{ config('app.name') }}
        </div>
    </div>
</body>
</html>
