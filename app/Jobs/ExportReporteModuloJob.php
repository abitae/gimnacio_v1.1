<?php

namespace App\Jobs;

use App\Data\Reporte\ReporteSucursalFilter;
use App\Exports\ReporteCajasExport;
use App\Exports\ReporteClientesExport;
use App\Exports\ReporteClientesMembresiaClasesExport;
use App\Exports\ReporteFinancieroExport;
use App\Exports\ReporteGimnasioExport;
use App\Exports\ReporteMatriculasExport;
use App\Exports\ReporteProductosServiciosExport;
use App\Exports\ReporteUsuariosExport;
use App\Exports\ReporteVentasExport;
use App\Models\User;
use App\Notifications\ReporteModuloExportListo;
use App\Services\ReporteModuloPdfService;
use App\Services\ReporteModuloService;
use App\Services\SucursalContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class ExportReporteModuloJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public function __construct(
        public int $userId,
        public ?int $sucursalId,
        public ?int $empresaId,
        public string $modulo,
        public string $format,
        public array $query
    ) {}

    public function handle(
        ReporteModuloService $reporteService,
        ReporteModuloPdfService $pdfService,
        SucursalContext $sucursalContext
    ): void {
        $filter = ReporteSucursalFilter::fromArray($this->query);

        if ($filter->isActive() && $this->sucursalId !== null) {
            $sucursalContext->setDelegateContext($this->sucursalId, $this->empresaId);
        }

        try {
            $user = User::findOrFail($this->userId);
            $q = $this->query;
            $fechaDesde = $q['fecha_desde'] ?? null;
            $fechaHasta = $q['fecha_hasta'] ?? null;
            $stamp = now()->format('Y-m-d_His');
            $slugBase = 'reporte_'.$this->modulo.'_'.$stamp;
            $dir = 'exports/reportes/'.$this->userId;

            Storage::disk('local')->makeDirectory($dir);

            if ($this->format === 'excel') {
                [$export, $filename] = $this->makeExcelExport($reporteService, $fechaDesde, $fechaHasta, $q, $slugBase);
                $relativePath = $dir.'/'.$filename;
                Excel::store($export, $relativePath, 'local');
            } elseif ($this->format === 'pdf') {
                [$pdfBinary, $filename] = $this->makePdfExport($reporteService, $pdfService, $fechaDesde, $fechaHasta, $q, $slugBase);
                $relativePath = $dir.'/'.$filename;
                Storage::disk('local')->put($relativePath, $pdfBinary);
            } else {
                return;
            }

            $exportRef = (string) Str::uuid();
            $user->notify(new ReporteModuloExportListo(
                $exportRef,
                $relativePath,
                basename($relativePath),
                $this->modulo,
                $this->format
            ));
        } finally {
            $sucursalContext->clearDelegateContext();
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(SucursalContext::class)->clearDelegateContext();
    }

    /**
     * @return array{0: object, 1: string}
     */
    protected function makeExcelExport(
        ReporteModuloService $reporteService,
        mixed $fechaDesde,
        mixed $fechaHasta,
        array $q,
        string $slugBase
    ): array {
        $filename = $slugBase.'.xlsx';
        $filter = ReporteSucursalFilter::fromArray($q);

        return match ($this->modulo) {
            'ventas' => [
                new ReporteVentasExport($reporteService->datosReporteVentas($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            'matriculas' => [
                new ReporteMatriculasExport($reporteService->datosReporteMatriculas($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            'financiero' => [
                new ReporteFinancieroExport($reporteService->datosReporteFinanciero($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            'clientes' => [
                new ReporteClientesExport($reporteService->datosReporteClientes(
                    isset($q['estado']) && $q['estado'] !== '' ? (string) $q['estado'] : null,
                    $fechaDesde,
                    $fechaHasta,
                    isset($q['created_by']) && $q['created_by'] !== '' ? (int) $q['created_by'] : null,
                    isset($q['trainer_user_id']) && $q['trainer_user_id'] !== '' ? (int) $q['trainer_user_id'] : null,
                    isset($q['vigencia']) && $q['vigencia'] !== '' ? (string) $q['vigencia'] : null,
                    max(1, (int) ($q['ventana_dias'] ?? 15)),
                    $filter,
                )),
                $filename,
            ],
            'clientes-membresia-clases' => [
                new ReporteClientesMembresiaClasesExport($reporteService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            'usuarios' => [
                new ReporteUsuariosExport($reporteService->datosReporteUsuarios($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            'cajas' => [
                new ReporteCajasExport($reporteService->datosReporteCajas(
                    $fechaDesde,
                    $fechaHasta,
                    null,
                    isset($q['usuario_id']) && $q['usuario_id'] !== '' ? (int) $q['usuario_id'] : null,
                    null,
                    $filter,
                )),
                $filename,
            ],
            'productos-servicios' => [
                new ReporteProductosServiciosExport($reporteService->datosReporteProductosServicios($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            'gimnasio' => [
                new ReporteGimnasioExport($reporteService->datosReporteGimnasio($fechaDesde, $fechaHasta, $filter)),
                $filename,
            ],
            default => throw new \InvalidArgumentException('Módulo de exportación no soportado: '.$this->modulo),
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function makePdfExport(
        ReporteModuloService $reporteService,
        ReporteModuloPdfService $pdfService,
        mixed $fechaDesde,
        mixed $fechaHasta,
        array $q,
        string $slugBase
    ): array {
        $filename = $slugBase.'.pdf';
        $filter = ReporteSucursalFilter::fromArray($q);

        $data = match ($this->modulo) {
            'ventas' => tap(
                $reporteService->datosReporteVentas($fechaDesde, $fechaHasta, $filter),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'matriculas' => tap(
                $reporteService->datosReporteMatriculas($fechaDesde, $fechaHasta, $filter),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'financiero' => tap(
                $reporteService->datosReporteFinanciero($fechaDesde, $fechaHasta, $filter),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'clientes' => tap(
                $reporteService->datosReporteClientes(
                    isset($q['estado']) && $q['estado'] !== '' ? (string) $q['estado'] : null,
                    $fechaDesde,
                    $fechaHasta,
                    isset($q['created_by']) && $q['created_by'] !== '' ? (int) $q['created_by'] : null,
                    isset($q['trainer_user_id']) && $q['trainer_user_id'] !== '' ? (int) $q['trainer_user_id'] : null,
                    isset($q['vigencia']) && $q['vigencia'] !== '' ? (string) $q['vigencia'] : null,
                    max(1, (int) ($q['ventana_dias'] ?? 15)),
                    $filter,
                ),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'clientes-membresia-clases' => tap(
                $reporteService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta, $filter),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'usuarios' => tap(
                $reporteService->datosReporteUsuarios($fechaDesde, $fechaHasta, $filter),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'cajas' => tap(
                $reporteService->datosReporteCajas(
                    $fechaDesde,
                    $fechaHasta,
                    null,
                    isset($q['usuario_id']) && $q['usuario_id'] !== '' ? (int) $q['usuario_id'] : null,
                    null,
                    $filter,
                ),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'productos-servicios' => tap(
                $reporteService->datosReporteProductosServicios($fechaDesde, $fechaHasta, $filter),
                function (&$d) use ($fechaDesde, $fechaHasta) {
                    $d['fecha_desde'] = $fechaDesde ?: '—';
                    $d['fecha_hasta'] = $fechaHasta ?: '—';
                }
            ),
            'gimnasio' => tap(
                $reporteService->datosReporteGimnasio($fechaDesde, $fechaHasta, $filter),
                function (&$d) {
                    $d['fecha_desde'] = $d['fecha_desde'] ?? '—';
                    $d['fecha_hasta'] = $d['fecha_hasta'] ?? '—';
                }
            ),
            default => throw new \InvalidArgumentException('Módulo de exportación no soportado: '.$this->modulo),
        };

        $pdf = match ($this->modulo) {
            'ventas' => $pdfService->generarPdfVentas($data),
            'matriculas' => $pdfService->generarPdfMatriculas($data),
            'financiero' => $pdfService->generarPdfFinanciero($data),
            'clientes' => $pdfService->generarPdfClientes($data),
            'clientes-membresia-clases' => $pdfService->generarPdfClientesMembresiaClases($data),
            'usuarios' => $pdfService->generarPdfUsuarios($data),
            'cajas' => $pdfService->generarPdfCajas($data),
            'productos-servicios' => $pdfService->generarPdfProductosServicios($data),
            'gimnasio' => $pdfService->generarPdfGimnasio($data),
            default => throw new \InvalidArgumentException('Módulo PDF no soportado: '.$this->modulo),
        };

        return [$pdf, $filename];
    }
}
