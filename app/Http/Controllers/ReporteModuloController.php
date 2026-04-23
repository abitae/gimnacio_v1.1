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
use App\Jobs\ExportReporteModuloJob;
use App\Services\ReporteModuloPdfService;
use App\Services\ReporteModuloService;
use App\Services\SucursalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

    /**
     * Si REPORTES_QUEUE_EXPORTS=true, encola la generación y notifica al usuario.
     */
    protected function dispatchQueuedExportIfEnabled(Request $request, string $modulo, string $format): ?RedirectResponse
    {
        if (! config('reportes.queue_exports')) {
            return null;
        }

        $sucursal = app(SucursalContext::class)->sucursal();

        ExportReporteModuloJob::dispatch(
            (int) Auth::id(),
            $sucursal?->id,
            $sucursal?->empresa_id,
            $modulo,
            $format,
            $request->query->all()
        );

        return redirect()->back()->with(
            'success',
            'La exportación se está generando en segundo plano. Cuando termine, podrás descargarla desde las notificaciones (ícono de campana).'
        );
    }

    public function exportarPdfVentas(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'ventas', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteVentas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfVentas($data);
        $nombre = 'reporte_ventas_'.now()->format('Y-m-d_His').'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }

    public function exportarPdfMatriculas(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'matriculas', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteMatriculas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfMatriculas($data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_matriculas_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfFinanciero(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'financiero', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteFinanciero($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfFinanciero($data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_financiero_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfClientes(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'clientes', 'pdf')) {
            return $redirect;
        }
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
            'Content-Disposition' => 'attachment; filename="reporte_clientes_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfClientesMembresiaClases(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'clientes-membresia-clases', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfClientesMembresiaClases($data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_clientes_membresia_clases_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfUsuarios(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'usuarios', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteUsuarios($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfUsuarios($data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_usuarios_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfCajas(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'cajas', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteCajas(
            $fechaDesde,
            $fechaHasta,
            $request->integer('sucursal_id') ?: null,
            $request->integer('usuario_id') ?: null,
            $request->integer('caja_id') ?: null,
        );
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $data['formato'] = $request->filled('caja_id') ? 'ticket' : 'reporte';
        $pdf = $this->pdfService->generarPdfCajas($data);
        $disposition = $request->boolean('inline') ? 'inline' : 'attachment';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="reporte_cajas_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfProductosServicios(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'productos-servicios', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteProductosServicios($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $fechaDesde ?: '—';
        $data['fecha_hasta'] = $fechaHasta ?: '—';
        $pdf = $this->pdfService->generarPdfProductosServicios($data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_productos_servicios_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarPdfGimnasio(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'gimnasio', 'pdf')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteGimnasio($fechaDesde, $fechaHasta);
        $data['fecha_desde'] = $data['fecha_desde'] ?? '—';
        $data['fecha_hasta'] = $data['fecha_hasta'] ?? '—';
        $pdf = $this->pdfService->generarPdfGimnasio($data);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="reporte_gimnasio_'.now()->format('Y-m-d_His').'.pdf"',
        ]);
    }

    public function exportarExcelVentas(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'ventas', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteVentas($fechaDesde, $fechaHasta);

        return (new ReporteVentasExport($data))->download('reporte_ventas_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelMatriculas(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'matriculas', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteMatriculas($fechaDesde, $fechaHasta);

        return (new ReporteMatriculasExport($data))->download('reporte_matriculas_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelFinanciero(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'financiero', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteFinanciero($fechaDesde, $fechaHasta);

        return (new ReporteFinancieroExport($data))->download('reporte_financiero_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelClientes(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'clientes', 'excel')) {
            return $redirect;
        }
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

        return (new ReporteClientesExport($data))->download('reporte_clientes_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelClientesMembresiaClases(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'clientes-membresia-clases', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta);

        return (new ReporteClientesMembresiaClasesExport($data))->download('reporte_clientes_membresia_clases_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelUsuarios(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'usuarios', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteUsuarios($fechaDesde, $fechaHasta);

        return (new ReporteUsuariosExport($data))->download('reporte_usuarios_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelCajas(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'cajas', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteCajas(
            $fechaDesde,
            $fechaHasta,
            $request->integer('sucursal_id') ?: null,
            $request->integer('usuario_id') ?: null,
            $request->integer('caja_id') ?: null,
        );

        return (new ReporteCajasExport($data))->download('reporte_cajas_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelProductosServicios(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'productos-servicios', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteProductosServicios($fechaDesde, $fechaHasta);

        return (new ReporteProductosServiciosExport($data))->download('reporte_productos_servicios_'.now()->format('Y-m-d_His').'.xlsx');
    }

    public function exportarExcelGimnasio(Request $request): Response|RedirectResponse
    {
        $this->authorize('reporte.ver');
        if ($redirect = $this->dispatchQueuedExportIfEnabled($request, 'gimnasio', 'excel')) {
            return $redirect;
        }
        [$fechaDesde, $fechaHasta] = $this->filtrosBasicos($request);
        $data = $this->reporteService->datosReporteGimnasio($fechaDesde, $fechaHasta);

        return (new ReporteGimnasioExport($data))->download('reporte_gimnasio_'.now()->format('Y-m-d_His').'.xlsx');
    }
}
