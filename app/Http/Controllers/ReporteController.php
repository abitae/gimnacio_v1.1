<?php

namespace App\Http\Controllers;

use App\Services\ReporteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReporteController extends Controller
{
    public function __construct(
        protected ReporteService $reporteService
    ) {}

    /**
     * Previsualizar reporte de evaluación (inline PDF para modal/iframe).
     */
    public function previewEvaluacion(int $evaluacionId): Response
    {
        return $this->reporteService->respuestaPreviewEvaluacion($evaluacionId);
    }

    /**
     * Descargar reporte de evaluación.
     */
    public function descargarEvaluacion(int $evaluacionId): Response
    {
        return $this->reporteService->respuestaDescargaEvaluacion($evaluacionId);
    }

    /**
     * Previsualizar historial de cliente.
     */
    public function previewHistorialCliente(Request $request, int $clienteId): Response
    {
        $filtros = $request->only(['estado', 'fecha_desde', 'fecha_hasta']);

        return $this->reporteService->respuestaPreviewHistorialCliente($clienteId, $filtros);
    }

    /**
     * Descargar historial de cliente.
     */
    public function descargarHistorialCliente(Request $request, int $clienteId): Response
    {
        $filtros = $request->only(['estado', 'fecha_desde', 'fecha_hasta']);

        return $this->reporteService->respuestaDescargaHistorialCliente($clienteId, $filtros);
    }

    /**
     * Previsualizar composición corporal.
     */
    public function previewComposicionCorporal(int $clienteId): Response
    {
        return $this->reporteService->respuestaPreviewComposicionCorporal($clienteId);
    }

    /**
     * Descargar composición corporal.
     */
    public function descargarComposicionCorporal(int $clienteId): Response
    {
        return $this->reporteService->respuestaDescargaComposicionCorporal($clienteId);
    }
}
