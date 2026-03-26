@php
    $clienteNombre = $pago->cliente
        ? trim($pago->cliente->nombres.' '.$pago->cliente->apellidos)
        : '—';
    $concepto = 'Cobro';
    if ($pago->clienteMatricula) {
        $m = $pago->clienteMatricula;
        $concepto = ucfirst((string) $m->tipo).' — '.$m->nombre;
    } elseif ($pago->clienteMembresia) {
        $concepto = 'Membresía — '.($pago->clienteMembresia->membresia->nombre ?? 'N/A');
    }
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Ticket {{ $pago->comprobante_numero ?? $pago->id }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 8pt; color: #000; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }
        .line { border-bottom: 1px dashed #000; margin: 4px 0; }
    </style>
</head>
<body>
    <p class="text-center font-bold" style="font-size: 10pt; margin-bottom: 4px;">TICKET DE COBRO</p>
    <p class="text-center" style="font-size: 7pt; margin-bottom: 6px;">
        {{ strtoupper((string) ($pago->comprobante_tipo ?? 'ticket')) }} {{ $pago->comprobante_numero ?? '—' }}
        &nbsp;|&nbsp; Pago #{{ $pago->id }}
        &nbsp;|&nbsp; {{ $pago->fecha_pago?->format('d/m/Y H:i') }}
    </p>
    <div class="line"></div>

    <p style="margin: 2px 0;"><strong>Cliente:</strong> {{ $clienteNombre }}</p>
    <p style="margin: 2px 0;"><strong>Concepto:</strong> {{ $concepto }}</p>
    <p style="margin: 2px 0;"><strong>Pago:</strong> {{ $pago->paymentMethod?->nombre ?? $pago->metodo_pago }}</p>
    @if($pago->numero_operacion)
        <p style="margin: 2px 0;"><strong>Nº oper.:</strong> {{ $pago->numero_operacion }}</p>
    @endif
    @if($pago->entidad_financiera)
        <p style="margin: 2px 0;"><strong>Entidad:</strong> {{ $pago->entidad_financiera }}</p>
    @endif
    <div class="line"></div>

    <p class="text-right font-bold" style="font-size: 10pt; margin-top: 6px;">
        TOTAL: {{ $pago->moneda === 'USD' ? '$' : 'S/' }} {{ number_format((float) $pago->monto, 2) }}
    </p>
    @if($pago->es_pago_parcial && (float) $pago->saldo_pendiente > 0)
        <p class="text-right" style="margin: 2px 0;">Saldo pendiente: {{ $pago->moneda === 'USD' ? '$' : 'S/' }} {{ number_format((float) $pago->saldo_pendiente, 2) }}</p>
    @endif
    <div class="line"></div>
    <p style="margin: 2px 0; font-size: 7pt;">Registrado por: {{ $pago->registradoPor?->name ?? '—' }}</p>
</body>
</html>
