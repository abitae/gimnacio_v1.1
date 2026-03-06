<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Historial de evaluaciones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        h1 { font-size: 16px; margin: 0 0 4px 0; border-bottom: 1px solid #333; padding-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        th, td { padding: 4px 8px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: 600; }
    </style>
</head>
<body>
    <h1>Historial de evaluaciones</h1>
    <p><strong>Cliente:</strong> {{ $cliente->nombres ?? '' }} {{ $cliente->apellidos ?? '' }}</p>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Peso (kg)</th>
                <th>IMC</th>
                <th>% Grasa</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($evaluaciones as $e)
            <tr>
                <td>{{ $e->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $e->peso ? number_format($e->peso, 2) : '-' }}</td>
                <td>{{ $e->imc ? number_format($e->imc, 2) : '-' }}</td>
                <td>{{ $e->porcentaje_grasa !== null ? number_format($e->porcentaje_grasa, 2) : '-' }}</td>
                <td>{{ $e->estado ?? '-' }}</td>
            </tr>
            @empty
            <tr><td colspan="5">Sin evaluaciones.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
