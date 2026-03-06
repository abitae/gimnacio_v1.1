<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Evaluación</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1f2937; line-height: 1.45; margin: 0; }
        .report-header { border-bottom: 2px solid #374151; padding-bottom: 10px; margin-bottom: 14px; }
        .report-title { font-size: 18px; font-weight: bold; color: #111827; margin: 0 0 4px 0; letter-spacing: -0.02em; }
        .report-subtitle { font-size: 11px; color: #4b5563; margin: 0; }
        .report-meta { font-size: 9px; color: #6b7280; margin-top: 6px; }
        .section { margin-bottom: 16px; }
        .section-title { font-size: 11px; font-weight: bold; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 8px 0; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb; }
        table { border-collapse: collapse; width: 100%; }
        .table-metrics th, .table-metrics td { padding: 5px 10px; text-align: left; border: 1px solid #e5e7eb; }
        .table-metrics th { background: #f9fafb; font-weight: 600; color: #374151; width: 55%; }
        .table-metrics td.value { text-align: right; font-weight: 500; }
        .table-grid th, .table-grid td { padding: 4px 8px; border: 1px solid #e5e7eb; font-size: 9px; }
        .table-grid th { background: #f3f4f6; font-weight: 600; }
        .table-grid td.value { text-align: right; }
        .chart-cell { width: 260px; padding: 0 12px 0 0; vertical-align: top; }
        .chart-cell img { display: block; max-width: 240px; height: auto; }
        .metrics-cell { vertical-align: top; }
        .highlight-row td { background: #f9fafb; font-weight: 500; }
        .two-cols { width: 100%; }
        .two-cols td { width: 50%; vertical-align: top; padding-right: 12px; }
        .observaciones { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 2px; padding: 8px 10px; margin-top: 6px; font-size: 9px; color: #4b5563; }
        .footer-report { margin-top: 20px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #9ca3af; text-align: center; }
    </style>
</head>
<body>

    <div class="report-header">
        <h1 class="report-title">Reporte de Evaluación</h1>
        <p class="report-subtitle">{{ $evaluacion->cliente->nombres ?? '' }} {{ $evaluacion->cliente->apellidos ?? '' }} · {{ $evaluacion->cliente->tipo_documento ?? '' }} {{ $evaluacion->cliente->numero_documento ?? '' }}</p>
        <p class="report-meta">Evaluación del {{ $evaluacion->created_at->format('d/m/Y') }} a las {{ $evaluacion->created_at->format('H:i') }}</p>
    </div>

    <div class="section">
        <h2 class="section-title">Composición corporal</h2>
        <table class="two-cols">
            <tr>
                <td class="chart-cell">
                    @if(!empty($chartImageBase64))
                    <img src="data:image/png;base64,{{ $chartImageBase64 }}" alt="Composición corporal" />
                    @else
                    <p style="color:#9ca3af; font-size:9px;">Sin datos para gráfico.</p>
                    @endif
                </td>
                <td class="metrics-cell">
                    <table class="table-metrics">
                        <tr class="highlight-row"><th>Peso</th><td class="value">{{ $evaluacion->peso ? number_format($evaluacion->peso, 1) . ' kg' : '-' }}</td></tr>
                        <tr><th>Estatura</th><td class="value">{{ $evaluacion->estatura ? number_format($evaluacion->estatura, 2) . ' m' : '-' }}</td></tr>
                        <tr class="highlight-row"><th>IMC</th><td class="value">{{ $evaluacion->imc ? number_format($evaluacion->imc, 2) : '-' }}</td></tr>
                        <tr><th>Porcentaje grasa</th><td class="value">{{ $evaluacion->porcentaje_grasa !== null ? number_format($evaluacion->porcentaje_grasa, 1) . ' %' : '-' }}</td></tr>
                        <tr><th>Porcentaje músculo</th><td class="value">{{ $evaluacion->porcentaje_musculo !== null ? number_format($evaluacion->porcentaje_musculo, 1) . ' %' : '-' }}</td></tr>
                        <tr><th>Masa muscular</th><td class="value">{{ $evaluacion->masa_muscular !== null ? number_format($evaluacion->masa_muscular, 1) . ' kg' : '-' }}</td></tr>
                        <tr><th>Masa grasa</th><td class="value">{{ $evaluacion->masa_grasa !== null ? number_format($evaluacion->masa_grasa, 1) . ' kg' : '-' }}</td></tr>
                        <tr><th>Masa ósea</th><td class="value">{{ $evaluacion->masa_osea !== null ? number_format($evaluacion->masa_osea, 1) . ' kg' : '-' }}</td></tr>
                        <tr><th>Masa residual</th><td class="value">{{ $evaluacion->masa_residual !== null ? number_format($evaluacion->masa_residual, 1) . ' kg' : '-' }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    @php $circ = is_array($evaluacion->circunferencias) ? $evaluacion->circunferencias : []; @endphp
    <div class="section">
        <h2 class="section-title">Circunferencias (cm)</h2>
        <table class="table-grid">
            <tr>
                <th>Medida</th><td class="value">{{ isset($circ['estatura']) && $circ['estatura'] != '' ? number_format((float)$circ['estatura'], 1) : '-' }}</td>
                <th>Cuello</th><td class="value">{{ isset($circ['cuello']) && $circ['cuello'] != '' ? number_format((float)$circ['cuello'], 1) : '-' }}</td>
            </tr>
            <tr>
                <th>Brazo normal</th><td class="value">{{ isset($circ['brazo_normal']) && $circ['brazo_normal'] != '' ? number_format((float)$circ['brazo_normal'], 1) : '-' }}</td>
                <th>Brazo contraído</th><td class="value">{{ isset($circ['brazo_contraido']) && $circ['brazo_contraido'] != '' ? number_format((float)$circ['brazo_contraido'], 1) : '-' }}</td>
            </tr>
            <tr>
                <th>Tórax</th><td class="value">{{ isset($circ['torax']) && $circ['torax'] != '' ? number_format((float)$circ['torax'], 1) : '-' }}</td>
                <th>Cintura</th><td class="value">{{ isset($circ['cintura']) && $circ['cintura'] != '' ? number_format((float)$circ['cintura'], 1) : '-' }}</td>
            </tr>
            <tr>
                <th>Cintura baja</th><td class="value">{{ isset($circ['cintura_baja']) && $circ['cintura_baja'] != '' ? number_format((float)$circ['cintura_baja'], 1) : '-' }}</td>
                <th>Cadera</th><td class="value">{{ isset($circ['cadera']) && $circ['cadera'] != '' ? number_format((float)$circ['cadera'], 1) : '-' }}</td>
            </tr>
            <tr>
                <th>Muslo</th><td class="value">{{ isset($circ['muslo']) && $circ['muslo'] != '' ? number_format((float)$circ['muslo'], 1) : '-' }}</td>
                <th>Glúteos</th><td class="value">{{ isset($circ['gluteos']) && $circ['gluteos'] != '' ? number_format((float)$circ['gluteos'], 1) : '-' }}</td>
            </tr>
            <tr>
                <th>Pantorrilla</th><td class="value">{{ isset($circ['pantorrilla']) && $circ['pantorrilla'] != '' ? number_format((float)$circ['pantorrilla'], 1) : '-' }}</td>
                <th></th><td></td>
            </tr>
        </table>
    </div>

    @if($evaluacion->presion_arterial || $evaluacion->frecuencia_cardiaca || $evaluacion->objetivo)
    <div class="section">
        <h2 class="section-title">Otros datos</h2>
        <table class="table-metrics" style="max-width: 400px;">
            @if($evaluacion->presion_arterial)<tr><th>Presión arterial</th><td class="value">{{ $evaluacion->presion_arterial }}</td></tr>@endif
            @if($evaluacion->frecuencia_cardiaca)<tr><th>Frecuencia cardíaca</th><td class="value">{{ $evaluacion->frecuencia_cardiaca }}</td></tr>@endif
            @if($evaluacion->objetivo)<tr><th>Objetivo</th><td class="value">{{ $evaluacion->objetivo }}</td></tr>@endif
        </table>
    </div>
    @endif

    @if($evaluacion->observaciones)
    <div class="section">
        <h2 class="section-title">Observaciones</h2>
        <div class="observaciones">{{ $evaluacion->observaciones }}</div>
    </div>
    @endif

    <div class="footer-report">Documento generado el {{ now()->format('d/m/Y H:i') }} · Reporte de evaluación</div>

</body>
</html>
