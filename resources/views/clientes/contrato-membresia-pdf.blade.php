<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato de membresía</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; line-height: 1.45; }
        h1 { font-size: 13px; text-align: center; margin: 0 0 4px 0; text-transform: uppercase; }
        h2 { font-size: 11px; text-align: center; margin: 0 0 12px 0; font-weight: normal; }
        .intro { text-align: justify; margin-bottom: 12px; }
        .section { margin-bottom: 10px; }
        .section-title { font-weight: bold; margin: 0 0 4px 0; font-size: 10.5px; }
        .field-line { margin: 2px 0; }
        .field-label { font-weight: bold; }
        .checks { margin: 4px 0; }
        .checks span { margin-right: 14px; white-space: nowrap; }
        ul { margin: 4px 0 4px 16px; padding: 0; }
        li { margin-bottom: 2px; }
        ol { margin: 4px 0 4px 16px; padding: 0; }
        .signature { margin-top: 18px; }
        .muted { color: #444; }
    </style>
</head>
<body>
@php
    $chk = fn (bool $on) => $on ? '☑' : '☐';
    $tipo = $contrato['tipo_membresia'];
@endphp

<h1>CONTRATO DE MEMBRESÍA Y PRESTACIÓN DE SERVICIOS</h1>
<h2>{{ $contrato['gimnasio_nombre_titulo'] }}</h2>

<p class="intro">
    Por el presente documento, {{ $contrato['gimnasio_nombre'] }}, en adelante denominado <strong>“EL GIMNASIO”</strong>,
    y el cliente identificado como <strong>“EL AFILIADO”</strong>, acuerdan la prestación del servicio de entrenamiento físico
    bajo las siguientes condiciones:
</p>

<div class="section">
    <p class="section-title">1. DATOS DEL AFILIADO</p>
    @if (filled($contrato['afiliado_nombre']))
        <p class="field-line"><span class="field-label">Nombres y apellidos:</span> {{ $contrato['afiliado_nombre'] }}</p>
    @endif
    @if (filled($contrato['afiliado_dni']) || filled($contrato['afiliado_celular']))
        <p class="field-line">
            @if (filled($contrato['afiliado_dni']))
                <span class="field-label">DNI:</span> {{ $contrato['afiliado_dni'] }}
            @endif
            @if (filled($contrato['afiliado_dni']) && filled($contrato['afiliado_celular']))
                &nbsp;&nbsp;&nbsp;
            @endif
            @if (filled($contrato['afiliado_celular']))
                <span class="field-label">Celular:</span> {{ $contrato['afiliado_celular'] }}
            @endif
        </p>
    @endif
    @if (filled($contrato['afiliado_fecha_nacimiento']) || filled($contrato['afiliado_direccion']))
        <p class="field-line">
            @if (filled($contrato['afiliado_fecha_nacimiento']))
                <span class="field-label">Fecha de nacimiento:</span> {{ $contrato['afiliado_fecha_nacimiento'] }}
            @endif
            @if (filled($contrato['afiliado_fecha_nacimiento']) && filled($contrato['afiliado_direccion']))
                &nbsp;&nbsp;&nbsp;
            @endif
            @if (filled($contrato['afiliado_direccion']))
                <span class="field-label">Dirección:</span> {{ $contrato['afiliado_direccion'] }}
            @endif
        </p>
    @endif
    @if (filled($contrato['afiliado_codigo']) || filled($contrato['asesor_nombre']))
        <p class="field-line">
            @if (filled($contrato['afiliado_codigo']))
                <span class="field-label">Código de cliente:</span> {{ $contrato['afiliado_codigo'] }}
            @endif
            @if (filled($contrato['afiliado_codigo']) && filled($contrato['asesor_nombre']))
                &nbsp;&nbsp;&nbsp;
            @endif
            @if (filled($contrato['asesor_nombre']))
                <span class="field-label">Asesor de venta:</span> {{ $contrato['asesor_nombre'] }}
            @endif
        </p>
    @endif
</div>

<div class="section">
    <p class="section-title">2. DATOS DE LA MEMBRESÍA</p>
    <p class="field-line field-label">Tipo de membresía contratada:</p>
    <p class="checks">
        <span>{{ $chk($tipo['mensual']) }} Mensual</span>
        <span>{{ $chk($tipo['trimestral']) }} Trimestral</span>
        <span>{{ $chk($tipo['semestral']) }} Semestral</span>
        <span>{{ $chk($tipo['anual']) }} Anual</span>
        <span>{{ $chk($tipo['otro']) }} Otro{{ ($tipo['otro'] && filled($tipo['otro_texto'])) ? ': '.$tipo['otro_texto'] : '' }}</span>
    </p>
    <p class="field-line field-label">Sede:</p>
    <p class="checks">
        @foreach ($contrato['sedes'] as $nombreSede => $activa)
            <span>{{ $chk($activa) }} {{ $nombreSede }}</span>
        @endforeach
    </p>
    @if (filled($contrato['fecha_inicio']) || filled($contrato['fecha_termino']))
        <p class="field-line">
            @if (filled($contrato['fecha_inicio']))
                <span class="field-label">Fecha de inicio:</span> {{ $contrato['fecha_inicio'] }}
            @endif
            @if (filled($contrato['fecha_inicio']) && filled($contrato['fecha_termino']))
                &nbsp;&nbsp;&nbsp;
            @endif
            @if (filled($contrato['fecha_termino']))
                <span class="field-label">Fecha de término:</span> {{ $contrato['fecha_termino'] }}
            @endif
        </p>
    @endif
    @if (filled($contrato['monto_pagado']) || filled($contrato['forma_pago']))
        <p class="field-line">
            @if (filled($contrato['monto_pagado']))
                <span class="field-label">Monto pagado:</span> S/ {{ $contrato['monto_pagado'] }}
            @endif
            @if (filled($contrato['monto_pagado']) && filled($contrato['forma_pago']))
                &nbsp;&nbsp;&nbsp;
            @endif
            @if (filled($contrato['forma_pago']))
                <span class="field-label">Forma de pago:</span> {{ $contrato['forma_pago'] }}
            @endif
        </p>
    @endif
    @if (filled($contrato['fechas_pago_fraccionado']))
        <p class="field-line"><span class="field-label">Fechas de pago fraccionado:</span> {{ $contrato['fechas_pago_fraccionado'] }}</p>
    @endif
</div>

<div class="section">
    <p class="section-title">3. CONDICIONES DE PAGO</p>
    <p>El AFILIADO se compromete a realizar sus pagos en las fechas establecidas.</p>
    <p>En caso de atraso o falta de pago:</p>
    <ul>
        <li>El acceso al gimnasio será suspendido hasta regularizar la deuda.</li>
        <li>Los días no utilizados por falta de pago no serán recuperables.</li>
        <li>Mientras exista deuda pendiente, el AFILIADO no podrá solicitar congelamiento, cambio de plan, transferencia o beneficios adicionales.</li>
    </ul>
</div>

<div class="section">
    <p class="section-title">4. USO DE LA MEMBRESÍA</p>
    <ol>
        <li>La membresía es personal e intransferible.</li>
        <li>El AFILIADO no podrá prestar, vender ni permitir el ingreso de otra persona utilizando su membresía.</li>
        <li>El gimnasio podrá solicitar identificación para validar el ingreso.</li>
        <li>El uso de las instalaciones está sujeto a los horarios establecidos por cada sede.</li>
        <li>Los horarios de clase grupales podrían variar sin anticipación alguna sin responsabilidad del gimnasio, cada instructor de clases grupales es responsable de su horario.</li>
    </ol>
</div>

<div class="section">
    <p class="section-title">5. RESPONSABILIDAD DEL AFILIADO</p>
    <p>El AFILIADO declara:</p>
    <ul>
        <li>Encontrarse en condiciones adecuadas para realizar actividad física.</li>
        <li>Informar cualquier condición médica, lesión o limitación antes de iniciar entrenamiento. (al instructor de sala)</li>
        <li>Seguir las indicaciones de entrenadores, nutricionistas y personal autorizado.</li>
    </ul>
    <p>EL GIMNASIO no será responsable por lesiones ocasionadas por:</p>
    <ul>
        <li>Uso incorrecto de máquinas o equipos.</li>
        <li>No seguir indicaciones del personal.</li>
        <li>Realizar ejercicios sin autorización o de manera inadecuada.</li>
    </ul>
</div>

<div class="section">
    <p class="section-title">6. CUIDADO DE EQUIPOS E INSTALACIONES</p>
    <p>El AFILIADO se compromete a cuidar máquinas, accesorios y ambientes del gimnasio.</p>
    <p>En caso de daño ocasionado por mal uso o negligencia, EL AFILIADO deberá asumir el costo de reparación o reposición correspondiente.</p>
</div>

<div class="section">
    <p class="section-title">7. NORMAS DE CONVIVENCIA</p>
    <p>El AFILIADO debe mantener una conducta respetuosa dentro del gimnasio.</p>
    <p>Está prohibido:</p>
    <ul>
        <li>Agredir verbal o físicamente a clientes o trabajadores.</li>
        <li>Realizar actos que afecten la tranquilidad del establecimiento.</li>
        <li>Consumir alcohol, drogas o sustancias prohibidas.</li>
        <li>Comercializar productos o servicios personales dentro del gimnasio.</li>
        <li>Realizar entrenamientos personales sin autorización.</li>
    </ul>
    <p>El incumplimiento de estas normas puede generar la cancelación inmediata de la membresía sin devolución del dinero pagado.</p>
</div>

<div class="section">
    <p class="section-title">8. DEVOLUCIONES Y CANCELACIONES</p>
    <p>Los pagos realizados por membresías no son reembolsables una vez iniciado el servicio.</p>
    <p>La falta de asistencia del AFILIADO no genera devolución ni ampliación del tiempo contratado.</p>
    <p>Cualquier caso excepcional será evaluado por la administración de {{ $contrato['gimnasio_nombre'] }}.</p>
</div>

<div class="section">
    <p class="section-title">9. CONGELAMIENTO O REGALO DE DÍAS DE MEMBRESÍA</p>
    <p>El congelamiento o regalo dependerá del tipo de plan contratado:</p>
    <ul>
        <li>Plan anual: 30 días de regalo o congelamiento.</li>
        <li>Plan semestral: 20 días de regalo o congelamiento.</li>
        <li>Plan trimestral: 10 días de regalo o congelamiento.</li>
        <li>Plan mensual: 05 días de regalo o congelamiento.</li>
        <li>El plan low cost: no cuenta con congelamiento.</li>
    </ul>
    <p>Toda solicitud debe realizarse en recepción y encontrarse al día en pagos.</p>
</div>

<div class="section">
    <p class="section-title">10. LOCKERS Y OBJETOS PERSONALES</p>
    <p>Los lockers son únicamente un beneficio de uso temporal y no representan un servicio de custodia.</p>
    <p>Los lockers que permanezcan con objetos de un cliente por dos días consecutivos serán allanados y todo lo encontrado permanecerán en recepción del gimnasio por 3 días; pasados los días se procederá a donarlos o botarlos.</p>
    <p>EL GIMNASIO no se responsabiliza por pérdida, robo o daño de objetos personales como: celulares, dinero, tarjetas, documentos, joyas, audífonos u otros artículos.</p>
    <p>El AFILIADO deberá utilizar candado propio.</p>
    <p>Los locker se pueden alquilar mensualmente con un costo de S/. 30.00 al mes.</p>
</div>

<div class="section">
    <p class="section-title">11. COCHERA</p>
    <p>El servicio de cochera es un beneficio de algunas sedes y no forma parte de la membresía.</p>
    <p>El servicio de cochera es exclusivo para clientes al día en su pago y por solo 2 horas; pasado este tiempo se le cobrará por hora o fracción la suma de S/. 4.00.</p>
    <p>EL GIMNASIO no se responsabiliza por daños, pérdidas o pertenencias dejadas dentro de los vehículos.</p>
</div>

<div class="section">
    <p class="section-title">12. CAMBIOS EN SERVICIOS</p>
    <p>{{ $contrato['gimnasio_nombre'] }} podrá realizar modificaciones en:</p>
    <ul>
        <li>Horarios de clases.</li>
        <li>Distribución de ambientes.</li>
        <li>Personal de atención.</li>
        <li>Programación de actividades.</li>
    </ul>
    <p>Estos cambios serán comunicados oportunamente buscando mejorar el servicio.</p>
</div>

<div class="section">
    <p class="section-title">13. SERVICIOS ADICIONALES</p>
    <p>Los servicios adicionales como nutrición, entrenamientos personalizados u otros podrán tener costos adicionales según las tarifas vigentes.</p>
</div>

<div class="section">
    <p class="section-title">14. TERMINACIÓN DEL CONTRATO</p>
    <p>{{ $contrato['gimnasio_nombre'] }} podrá finalizar la membresía cuando el AFILIADO:</p>
    <ul>
        <li>Incumpla pagos.</li>
        <li>Incumpla normas internas.</li>
        <li>Dañe instalaciones.</li>
        <li>Mantenga conductas que afecten a otros clientes o trabajadores.</li>
    </ul>
    <p>La cancelación por incumplimiento no genera devolución de pagos realizados.</p>
</div>

<div class="section">
    <p class="section-title">15. ACEPTACIÓN</p>
    <p>El AFILIADO declara haber leído, comprendido y aceptado las condiciones del presente contrato y reglamento interno de {{ $contrato['gimnasio_nombre'] }}.</p>
    <p>[ {{ $chk(true) }} ] Acepto y me comprometo a cumplir el Reglamento de Usuario de {{ $contrato['gimnasio_nombre'] }}.</p>
</div>

<p class="signature">
    Firmado en {{ $contrato['ciudad_firma'] }}, {{ $contrato['fecha_firma_dia'] }} de {{ $contrato['fecha_firma_mes'] }} del 20{{ $contrato['fecha_firma_anio'] }}
</p>

<p class="muted" style="margin-top: 20px; font-size: 8px;">
    Documento generado el {{ now()->format('d/m/Y H:i') }} · Cliente: {{ $contrato['afiliado_codigo'] ?: $contrato['afiliado_nombre'] }}
</p>
</body>
</html>
