<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket de entradas de caja</title>
    <style>
        body {
            margin: 0;
            font-family: sans-serif;
            font-size: 9px;
            color: #111827;
        }
        .ticket { width: 100%; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        .title { font-size: 12px; font-weight: 700; }
        .subtitle { font-size: 10px; font-weight: 700; margin-top: 2px; }
        .section {
            margin-top: 8px;
            padding-top: 6px;
            border-top: 1px dashed #9ca3af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            vertical-align: top;
            padding: 1px 0;
        }
        .strong { font-weight: 700; }
        .item {
            padding: 4px 0;
            border-bottom: 1px dotted #d1d5db;
        }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="center">
            <div class="title">{{ config('app.name') }}</div>
            <div class="subtitle">Reporte detallado de entradas</div>
            <div class="muted">Caja #{{ $caja->id }}</div>
        </div>

        <div class="section">
            <table>
                <tr><td>Usuario</td><td class="right">{{ $caja->usuario?->name ?? '—' }}</td></tr>
                <tr><td>Sucursal</td><td class="right">{{ $caja->sucursal?->nombre ?? '—' }}</td></tr>
                <tr><td>Apertura</td><td class="right">{{ $caja->fecha_apertura?->format('d/m/Y H:i') ?? '—' }}</td></tr>
                <tr><td>Estado</td><td class="right">{{ ucfirst($caja->estado) }}</td></tr>
                <tr><td>Movimientos</td><td class="right">{{ $resumen['cantidad'] }}</td></tr>
                <tr><td class="strong">Total entradas</td><td class="right strong">S/ {{ number_format((float) $resumen['total'], 2) }}</td></tr>
            </table>
        </div>

        <div class="section">
            <div class="strong">Por tipo de entrada</div>
            @foreach ($resumen['tipos'] as $tipo)
                <div class="item">
                    <table>
                        <tr>
                            <td class="strong">{{ $tipo['label'] }}</td>
                            <td class="right strong">S/ {{ number_format((float) $tipo['total'], 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" class="muted">{{ $tipo['cantidad'] }} movimiento(s)</td>
                        </tr>
                        @foreach ($tipo['metodos'] as $metodo)
                            <tr>
                                <td>{{ $metodo['metodo'] }}</td>
                                <td class="right">S/ {{ number_format((float) $metodo['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        </div>

        <div class="section">
            <div class="strong">Por método de pago</div>
            @foreach ($resumen['metodos'] as $metodo)
                <div class="item">
                    <table>
                        <tr>
                            <td class="strong">Método: {{ $metodo['metodo'] }}</td>
                            <td class="right strong">S/ {{ number_format((float) $metodo['total'], 2) }}</td>
                        </tr>
                        @foreach ($metodo['tipos'] as $tipo)
                            <tr>
                                <td>Origen: {{ $tipo['label'] }}</td>
                                <td class="right">S/ {{ number_format((float) $tipo['total'], 2) }}</td>
                            </tr>
                        @endforeach
                    </table>
                </div>
            @endforeach
        </div>

        <div class="section">
            <div class="strong">Detalle</div>
            @forelse ($movimientos as $movimiento)
                <div class="item">
                    <table>
                        <tr>
                            <td class="strong">{{ $movimiento['concepto'] }}</td>
                            <td class="right strong">S/ {{ number_format((float) $movimiento['monto'], 2) }}</td>
                        </tr>
                        <tr>
                            <td>{{ $movimiento['fecha']?->format('d/m H:i') ?? '—' }}</td>
                            <td class="right">{{ $movimiento['tipo_visual'] ?? '—' }}</td>
                        </tr>
                        <tr>
                            <td>{{ $movimiento['metodo_pago'] ?: 'Sin método' }}</td>
                            <td class="right">{{ $movimiento['numero_operacion'] ?: ($movimiento['referencia_label'] ?: '—') }}</td>
                        </tr>
                        <tr>
                            <td>{{ $movimiento['usuario'] ?: '—' }}</td>
                            <td class="right">{{ $movimiento['entidad_financiera'] ?: '—' }}</td>
                        </tr>
                    </table>
                </div>
            @empty
                <div class="muted">Sin entradas registradas.</div>
            @endforelse
        </div>

        <div class="section center muted">
            Generado {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>
</body>
</html>
