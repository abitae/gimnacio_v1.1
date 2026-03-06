<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Clientes con membresía y clases activas</title>
    @include('reportes.modulo._estilos')
</head>
<body>
    <div class="report-header">
        <h1 class="report-title">Clientes con membresía y clases activas</h1>
        <p class="report-subtitle">Período pagos: {{ $fecha_desde ?? '—' }} al {{ $fecha_hasta ?? '—' }} · Generado: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="resumen-box">
        <strong>Resumen:</strong>
        Membresías activas: {{ $resumen['cantidad_membresias_activas'] }} ·
        Clases activas: {{ $resumen['cantidad_clases_activas'] }} ·
        Clientes con membresía activa: {{ $resumen['clientes_con_membresia_activa'] }} ·
        Clientes con clase activa: {{ $resumen['clientes_con_clase_activa'] }} ·
        <strong>Total pagos membresía: S/ {{ number_format($resumen['total_pagos_membresia'], 2) }}</strong> ({{ $resumen['cantidad_pagos_membresia'] }} pagos) ·
        <strong>Total pagos clases: S/ {{ number_format($resumen['total_pagos_clase'], 2) }}</strong> ({{ $resumen['cantidad_pagos_clase'] }} pagos)
    </div>

    <p class="section-title">Membresías activas</p>
    <table style="margin-bottom:12px;">
        <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Membresía / Producto</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th class="text-right">Precio</th>
        </tr>
        @php $n = 0; @endphp
        @foreach($membresias_activas ?? [] as $m)
            @php $n++; @endphp
            <tr>
                <td>{{ $n }}</td>
                <td>{{ $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '-' }}</td>
                <td>{{ $m->membresia?->nombre ?? 'N/A' }}</td>
                <td>{{ $m->fecha_inicio?->format('d/m/Y') }}</td>
                <td>{{ $m->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-right">S/ {{ number_format($m->precio_final ?? 0, 2) }}</td>
            </tr>
        @endforeach
        @foreach($matriculas_membresia_activas ?? [] as $mat)
            @php $n++; @endphp
            <tr>
                <td>{{ $n }}</td>
                <td>{{ $mat->cliente ? trim($mat->cliente->nombres . ' ' . $mat->cliente->apellidos) : '-' }}</td>
                <td>{{ $mat->nombre }}</td>
                <td>{{ $mat->fecha_inicio?->format('d/m/Y') }}</td>
                <td>{{ $mat->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-right">S/ {{ number_format($mat->precio_final ?? 0, 2) }}</td>
            </tr>
        @endforeach
        @if(($membresias_activas ?? collect())->isEmpty() && ($matriculas_membresia_activas ?? collect())->isEmpty())
            <tr><td colspan="6" class="text-center">Sin membresías activas</td></tr>
        @endif
    </table>

    <p class="section-title">Clases activas</p>
    <table style="margin-bottom:12px;">
        <tr>
            <th>#</th>
            <th>Cliente</th>
            <th>Clase</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th class="text-right">Precio</th>
        </tr>
        @foreach($matriculas_clase_activas ?? [] as $mat)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $mat->cliente ? trim($mat->cliente->nombres . ' ' . $mat->cliente->apellidos) : '-' }}</td>
                <td>{{ $mat->nombre }}</td>
                <td>{{ $mat->fecha_inicio?->format('d/m/Y') }}</td>
                <td>{{ $mat->fecha_fin?->format('d/m/Y') ?? '-' }}</td>
                <td class="text-right">S/ {{ number_format($mat->precio_final ?? 0, 2) }}</td>
            </tr>
        @endforeach
        @if(($matriculas_clase_activas ?? collect())->isEmpty())
            <tr><td colspan="6" class="text-center">Sin clases activas</td></tr>
        @endif
    </table>

    <p class="section-title">Pagos de membresía (período)</p>
    <table style="margin-bottom:12px;">
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Membresía</th>
            <th class="text-right">Monto</th>
        </tr>
        @foreach($pagos_membresia ?? [] as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                <td>{{ $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-' }}</td>
                <td>{{ $p->clienteMembresia?->membresia?->nombre ?? '-' }}</td>
                <td class="text-right">S/ {{ number_format($p->monto, 2) }}</td>
            </tr>
        @endforeach
        @if(($pagos_membresia ?? collect())->isEmpty())
            <tr><td colspan="5" class="text-center">Sin pagos en el período</td></tr>
        @endif
    </table>

    <p class="section-title">Pagos de clases (período)</p>
    <table>
        <tr>
            <th>#</th>
            <th>Fecha</th>
            <th>Cliente</th>
            <th>Clase</th>
            <th class="text-right">Monto</th>
        </tr>
        @foreach($pagos_clase ?? [] as $p)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $p->fecha_pago?->format('d/m/Y H:i') }}</td>
                <td>{{ $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-' }}</td>
                <td>{{ $p->clienteMatricula?->nombre ?? '-' }}</td>
                <td class="text-right">S/ {{ number_format($p->monto, 2) }}</td>
            </tr>
        @endforeach
        @if(($pagos_clase ?? collect())->isEmpty())
            <tr><td colspan="5" class="text-center">Sin pagos en el período</td></tr>
        @endif
    </table>

    <div class="footer-report">Reporte generado por {{ config('app.name') }} · {{ now()->format('d/m/Y H:i:s') }}</div>
</body>
</html>
