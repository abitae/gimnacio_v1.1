<?php

namespace App\Services;

use App\Models\Core\Cliente;
use App\Models\Core\EvaluacionMedidasNutricion;
use App\Services\WhatsApp\WhatsAppServiceInterface;
use Illuminate\Support\Facades\URL;
use Mpdf\Mpdf;
use Symfony\Component\HttpFoundation\Response;

class ReporteService
{
    public function __construct(
        protected WhatsAppServiceInterface $whatsAppService
    ) {}

    /**
     * Configuración por defecto de mPDF para todos los reportes.
     */
    protected function configMpdf(): array
    {
        return [
            'mode' => 'utf-8',
            'format' => 'A4',
            'orientation' => 'P',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ];
    }

    /**
     * Genera el contenido PDF (binario) del reporte de evaluación.
     * Usado tanto para previsualización como para descarga.
     */
    public function generarPdfEvaluacion(int $evaluacionId): string
    {
        $evaluacion = EvaluacionMedidasNutricion::with(['cliente', 'nutricionista', 'evaluadoPor'])
            ->findOrFail($evaluacionId);

        $chartImageBase64 = $this->generarImagenGraficoComposicion($evaluacion);

        $html = view('reportes.evaluacion', [
            'evaluacion' => $evaluacion,
            'chartImageBase64' => $chartImageBase64,
        ])->render();

        $mpdf = new Mpdf($this->configMpdf());
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /**
     * Genera imagen del gráfico de composición corporal (doughnut) vía QuickChart.
     * Retorna base64 para incrustar en el PDF o null si no hay datos.
     */
    protected function generarImagenGraficoComposicion(EvaluacionMedidasNutricion $evaluacion): ?string
    {
        $composicion = $evaluacion->composicion_corporal;
        $labels = [];
        $data = [];
        $colors = ['#3b82f6', '#ef4444', '#8b5cf6', '#f59e0b'];

        if (! empty($composicion['masa_muscular']['kg'])) {
            $labels[] = 'Masa Muscular';
            $data[] = (float) $composicion['masa_muscular']['kg'];
        }
        if (! empty($composicion['masa_grasa']['kg'])) {
            $labels[] = 'Masa Grasa';
            $data[] = (float) $composicion['masa_grasa']['kg'];
        }
        if (! empty($composicion['masa_osea']['kg'])) {
            $labels[] = 'Masa Ósea';
            $data[] = (float) $composicion['masa_osea']['kg'];
        }
        if (! empty($composicion['masa_residual']['kg'])) {
            $labels[] = 'Masa Residual';
            $data[] = (float) $composicion['masa_residual']['kg'];
        }

        if (empty($data)) {
            return null;
        }

        $backgroundColor = array_slice($colors, 0, count($data));
        $chartConfig = [
            'type' => 'doughnut',
            'data' => [
                'labels' => $labels,
                'datasets' => [[
                    'data' => $data,
                    'backgroundColor' => $backgroundColor,
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ]],
            ],
            'options' => [
                'plugins' => [
                    'legend' => ['position' => 'bottom'],
                ],
            ],
        ];

        $url = 'https://quickchart.io/chart?c=' . urlencode(json_encode($chartConfig)) . '&width=280&height=280';

        $context = stream_context_create([
            'http' => ['timeout' => 10],
            'ssl' => ['verify_peer' => true],
        ]);
        $imageContent = @file_get_contents($url, false, $context);

        if ($imageContent === false) {
            return null;
        }

        return base64_encode($imageContent);
    }

    /**
     * Respuesta HTTP para previsualizar el reporte en el navegador (modal/iframe).
     */
    public function respuestaPreviewEvaluacion(int $evaluacionId): Response
    {
        $evaluacion = EvaluacionMedidasNutricion::findOrFail($evaluacionId);
        $pdfContent = $this->generarPdfEvaluacion($evaluacionId);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="evaluacion_' . $evaluacion->id . '.pdf"',
        ]);
    }

    /**
     * Respuesta HTTP para descargar el reporte de evaluación.
     */
    public function respuestaDescargaEvaluacion(int $evaluacionId): Response
    {
        $evaluacion = EvaluacionMedidasNutricion::findOrFail($evaluacionId);
        $pdfContent = $this->generarPdfEvaluacion($evaluacionId);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="evaluacion_' . $evaluacion->id . '_' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    /**
     * Envía el reporte de evaluación por WhatsApp al teléfono del cliente.
     * Genera el PDF en memoria y lo envía vía el servicio de WhatsApp.
     *
     * @return array{ success: bool, message: string }
     */
    public function enviarReportePorWhatsApp(int $evaluacionId): array
    {
        $evaluacion = EvaluacionMedidasNutricion::with('cliente')->findOrFail($evaluacionId);
        $cliente = $evaluacion->cliente;
        $telefono = $cliente->telefono ?? '';

        if (empty(trim((string) $telefono))) {
            return ['success' => false, 'message' => 'El cliente no tiene teléfono registrado. Añade un número en la ficha del cliente para poder enviar por WhatsApp.'];
        }

        $destino = trim($telefono);
        if (! str_starts_with($destino, '+')) {
            $destino = preg_replace('/^0/', '', $destino);
            $destino = (str_starts_with($destino, '51') ? '+' : '+51') . $destino;
        }

        $pdfBinario = $this->generarPdfEvaluacion($evaluacionId);
        $pdfBase64 = base64_encode($pdfBinario);
        $nombreArchivo = 'reporte_evaluacion_' . $evaluacion->id . '_' . now()->format('Y-m-d') . '.pdf';

        $urlDescarga = $this->getUrlDescargaEvaluacionFirmada($evaluacion->id);
        $caption = 'Aqui tienes el link: ' . $urlDescarga;

        $result = $this->whatsAppService->enviarDocumento($destino, $pdfBase64, $nombreArchivo, $caption);

        if ($result['success']) {
            return ['success' => true, 'message' => 'Reporte enviado por WhatsApp.'];
        }

        return ['success' => false, 'message' => $result['error'] ?? 'Error al enviar.'];
    }

    /**
     * URL firmada temporal (48 h) para descargar el reporte de evaluación (para enviar por WhatsApp, etc.).
     */
    public function getUrlDescargaEvaluacionFirmada(int $evaluacionId): string
    {
        return URL::temporarySignedRoute(
            'reportes.evaluacion.descargar.signed',
            now()->addHours(48),
            ['evaluacionId' => $evaluacionId]
        );
    }

    /**
     * Genera el contenido PDF del historial de un cliente.
     */
    public function generarPdfHistorialCliente(int $clienteId, array $filtros = []): string
    {
        $cliente = Cliente::findOrFail($clienteId);

        $query = EvaluacionMedidasNutricion::with(['nutricionista', 'evaluadoPor'])
            ->where('cliente_id', $clienteId)
            ->orderBy('created_at', 'desc');

        if (! empty($filtros['estado'])) {
            $query->where('estado', $filtros['estado']);
        }
        if (! empty($filtros['fecha_desde'])) {
            $query->where('created_at', '>=', $filtros['fecha_desde']);
        }
        if (! empty($filtros['fecha_hasta'])) {
            $query->where('created_at', '<=', $filtros['fecha_hasta']);
        }

        $evaluaciones = $query->get();
        $html = view('reportes.historial-cliente', [
            'cliente' => $cliente,
            'evaluaciones' => $evaluaciones,
        ])->render();

        $mpdf = new Mpdf($this->configMpdf());
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /**
     * Respuesta para previsualizar historial cliente.
     */
    public function respuestaPreviewHistorialCliente(int $clienteId, array $filtros = []): Response
    {
        $pdfContent = $this->generarPdfHistorialCliente($clienteId, $filtros);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="historial_cliente_' . $clienteId . '.pdf"',
        ]);
    }

    /**
     * Respuesta para descargar historial cliente.
     */
    public function respuestaDescargaHistorialCliente(int $clienteId, array $filtros = []): Response
    {
        $pdfContent = $this->generarPdfHistorialCliente($clienteId, $filtros);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="historial_cliente_' . $clienteId . '_' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }

    /**
     * Genera el contenido PDF de composición corporal.
     */
    public function generarPdfComposicionCorporal(int $clienteId): string
    {
        $cliente = Cliente::findOrFail($clienteId);

        $evaluaciones = EvaluacionMedidasNutricion::where('cliente_id', $clienteId)
            ->where('estado', 'completada')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $html = view('reportes.composicion-corporal', [
            'cliente' => $cliente,
            'evaluaciones' => $evaluaciones,
        ])->render();

        $mpdf = new Mpdf($this->configMpdf());
        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }

    /**
     * Respuesta para previsualizar composición corporal.
     */
    public function respuestaPreviewComposicionCorporal(int $clienteId): Response
    {
        $pdfContent = $this->generarPdfComposicionCorporal($clienteId);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="composicion_corporal_' . $clienteId . '.pdf"',
        ]);
    }

    /**
     * Respuesta para descargar composición corporal.
     */
    public function respuestaDescargaComposicionCorporal(int $clienteId): Response
    {
        $pdfContent = $this->generarPdfComposicionCorporal($clienteId);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="composicion_corporal_' . $clienteId . '_' . now()->format('Y-m-d') . '.pdf"',
        ]);
    }
}
