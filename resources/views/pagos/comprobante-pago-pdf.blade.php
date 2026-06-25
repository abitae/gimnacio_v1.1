@php
    $clienteNombre = $pago->cliente ? trim($pago->cliente->nombres.' '.$pago->cliente->apellidos) : '—';
    $concepto = 'Cobro';
    if ($pago->clienteMatricula) {
        $concepto = ucfirst((string) $pago->clienteMatricula->tipo).' — '.$pago->clienteMatricula->nombre;
    } elseif ($pago->clienteMembresia) {
        $concepto = 'Membresía — '.($pago->clienteMembresia->membresia->nombre ?? 'N/A');
    } elseif ($pago->clientDebt?->venta) {
        $concepto = 'Venta crédito — '.$pago->clientDebt->venta->numero_venta;
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
        .brand-logo { max-width: 80px; max-height: 40px; margin: 0 auto 4px; display: block; }
        .muted { font-size: 7pt; color: #444; }
    </style>
</head>
<body>
    @if(!empty($appBrandLogoUrl))
        <img src="{{ $appBrandLogoUrl }}" alt="{{ $appBrandName ?? config('app.name') }}" class="brand-logo">
    @endif
    <p class="text-center font-bold" style="font-size: 10pt; margin-bottom: 2px;">{{ $appBrandName ?? config('app.name') }}</p>
    <p class="text-center muted" style="margin-bottom: 6px;">{{ $pago->sucursal?->nombre ?? 'Sucursal principal' }}</p>
    <p class="text-center font-bold" style="font-size: 9pt; margin-bottom: 4px;">TICKET DE COBRO</p>
    <p class="text-center muted" style="margin-bottom: 6px;">
        {{ strtoupper((string) ($pago->comprobante_tipo ?? 'ticket')) }} {{ $pago->comprobante_numero ?? '—' }} · Pago #{{ $pago->id }} · {{ $pago->fechaHoraPago()->format('d/m/Y H:i') }}
    </p>
    <div class="line"></div>

    <p style="margin: 2px 0;"><strong>Cliente:</strong> {{ $clienteNombre }}</p>
    <p style="margin: 2px 0;"><strong>Concepto:</strong> {{ $concepto }}</p>
    <p style="margin: 2px 0;"><strong>Método:</strong> {{ $pago->paymentMethod?->nombre ?? $pago->metodo_pago }}</p>
    @if($pago->numero_operacion)
        <p style="margin: 2px 0;"><strong>N° oper.:</strong> {{ $pago->numero_operacion }}</p>
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
