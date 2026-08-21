<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Totales por tipo y método de pago</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    @php
        $matriz = $resumen['matriz_tipo_metodo'] ?? [];
        $tipos = $matriz['tipos'] ?? [];
        $metodos = $matriz['metodos'] ?? [];
        $celdas = $matriz['celdas'] ?? [];
        $usuarioFiltro = $usuario_filtro ?? null;
        $sucursalEtiqueta = $sucursal_etiqueta ?? null;
    @endphp

    <div class="report-header">
        <h1 class="report-title">Totales por tipo y método de pago</h1>
        <p class="report-subtitle">
            Período: {{ $fecha_desde }} al {{ $fecha_hasta }}
            @if ($sucursalEtiqueta)
                · {{ $sucursalEtiqueta }}
            @endif
            @if ($usuarioFiltro)
                · Usuario: {{ $usuarioFiltro }}
            @endif
            · Generado: {{ now()->format('d/m/Y H:i') }}
        </p>
    </div>

    <div class="resumen-box">
        Solo ingresos reales de caja. Las ventas a crédito no se incluyen.
        <strong>Total período: S/ {{ number_format((float) ($matriz['total_general'] ?? 0), 2) }}</strong>
    </div>

    <p class="section-title">Matriz de ingresos</p>
    @if (empty($tipos) || empty($metodos))
        <p>Sin ingresos de caja en el período.</p>
    @else
        <table>
            <tr>
                <th>Tipo</th>
                @foreach ($metodos as $metodo)
                    <th class="text-right">{{ $metodo }}</th>
                @endforeach
                <th class="text-right">Total</th>
            </tr>
            @foreach ($tipos as $tipo)
                <tr>
                    <td>{{ $tipo }}</td>
                    @foreach ($metodos as $metodo)
                        @php $celda = $celdas[$tipo][$metodo] ?? null; @endphp
                        <td class="text-right">
                            @if ($celda)
                                S/ {{ number_format((float) $celda['total'], 2) }}
                                @if (! empty($celda['cantidad']))
                                    ({{ $celda['cantidad'] }})
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    @endforeach
                    <td class="text-right"><strong>S/ {{ number_format((float) ($matriz['totales_tipo'][$tipo] ?? 0), 2) }}</strong></td>
                </tr>
            @endforeach
            <tr>
                <td><strong>Total período</strong></td>
                @foreach ($metodos as $metodo)
                    <td class="text-right"><strong>S/ {{ number_format((float) ($matriz['totales_metodo'][$metodo] ?? 0), 2) }}</strong></td>
                @endforeach
                <td class="text-right"><strong>S/ {{ number_format((float) ($matriz['total_general'] ?? 0), 2) }}</strong></td>
            </tr>
        </table>
    @endif

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
