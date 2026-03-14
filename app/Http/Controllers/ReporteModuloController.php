<?php

namespace App\Http\Controllers;

use App\Exports\ReporteCajasExport;
use App\Exports\ReporteClientesExport;
use App\Exports\ReporteClientesMembresiaClasesExport;
use App\Exports\ReporteFinancieroExport;
use App\Exports\ReporteGimnasioExport;
use App\Exports\ReporteMatriculasExport;
use App\Exports\ReporteProductosServiciosExport;
use App\Exports\ReporteUsuariosExport;
use App\Exports\ReporteVentasExport;
use App\Services\ReporteModuloPdfService;
use App\Services\ReporteModuloService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReporteModuloController extends Controller
{
    public function __construct(
        protected ReporteModuloService $reporteService,
        protected ReporteModuloPdfService $pdfService
    ) {}

    protected function filtrosBasicos(Request $request): array
    {
        return [
            $request->query('fecha_desde'),
            $request->query('fecha_hasta'),
        ];
    }

    public function exportarPdfVentas(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteVentas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfVentas($data);
        $nombre = 'reporte_ventas_' . now()->format('Y-m-d_His') . '.pdf';
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
        ]);
    }

    public function exportarPdfMatriculas(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteMatriculas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfMatriculas($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_matriculas_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfFinanciero(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteFinanciero($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfFinanciero($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_financiero_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfClientes(Request $request): Response
    {
        $this->authorize('reportes.view');
        $estado = $request->query('estado');
        $createdBy = $request->query('created_by');
        $trainerUserId = $request->query('trainer_user_id');
        $vigencia = $request->query('vigencia');
        $ventanaDias = (int) ($request->query('ventana_dias', 15));
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteClientes(
            $estado ?: null,
            $fechaDesde,
            $fechaHasta,
            $createdBy !== null && $createdBy !== '' ? (int) $createdBy : null,
            $trainerUserId !== null && $trainerUserId !== '' ? (int) $trainerUserId : null,
            $vigencia ?: null,
            $ventanaDias > 0 ? $ventanaDias : 15
        );
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfClientes($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_clientes_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfClientesMembresiaClases(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfClientesMembresiaClases($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_clientes_membresia_clases_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfUsuarios(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteUsuarios($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfUsuarios($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_usuarios_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfCajas(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteCajas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfCajas($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_cajas_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfProductosServicios(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteProductosServicios($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfProductosServicios($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_productos_servicios_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarPdfGimnasio(Request $request): Response
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteGimnasio($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $data['fecha_desde'] ?? '—';
        $data['fecha_hasta'] = $data['fecha_hasta'] ?? '—';
        $pdf = $this->pdfService->generarPdfGimnasio($data);
        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_gimnasio_' . now()->format('Y-m-d_His') . '.pdf"',
        ]);
    }

    public function exportarExcelVentas(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteVentas($fechaDesde, $fechaHasta);
        return (new ReporteVentasExport($data))->download('reporte_ventas_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelMatriculas(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteMatriculas($fechaDesde, $fechaHasta);
        return (new ReporteMatriculasExport($data))->download('reporte_matriculas_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelFinanciero(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteFinanciero($fechaDesde, $fechaHasta);
        return (new ReporteFinancieroExport($data))->download('reporte_financiero_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelClientes(Request $request)
    {
        $this->authorize('reportes.view');
        $estado = $request->query('estado');
        $createdBy = $request->query('created_by');
        $trainerUserId = $request->query('trainer_user_id');
        $vigencia = $request->query('vigencia');
        $ventanaDias = (int) ($request->query('ventana_dias', 15));
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteClientes(
            $estado ?: null,
            $fechaDesde,
            $fechaHasta,
            $createdBy !== null && $createdBy !== '' ? (int) $createdBy : null,
            $trainerUserId !== null && $trainerUserId !== '' ? (int) $trainerUserId : null,
            $vigencia ?: null,
            $ventanaDias > 0 ? $ventanaDias : 15
        );
        return (new ReporteClientesExport($data))->download('reporte_clientes_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelClientesMembresiaClases(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta);
        return (new ReporteClientesMembresiaClasesExport($data))->download('reporte_clientes_membresia_clases_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelUsuarios(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteUsuarios($fechaDesde, $fechaHasta);
        return (new ReporteUsuariosExport($data))->download('reporte_usuarios_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelCajas(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteCajas($fechaDesde, $fechaHasta);
        return (new ReporteCajasExport($data))->download('reporte_cajas_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelProductosServicios(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteProductosServicios($fechaDesde, $fechaHasta);
        return (new ReporteProductosServiciosExport($data))->download('reporte_productos_servicios_' . now()->format('Y-m-d_His') . '.xlsx');
    }

    public function exportarExcelGimnasio(Request $request)
    {
        $this->authorize('reportes.view');
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteGimnasio($fechaDesde, $fechaHasta);
        return (new ReporteGimnasioExport($data))->download('reporte_gimnasio_' . now()->format('Y-m-d_His') . '.xlsx');
    }
}
