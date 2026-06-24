<?php

namespace App\Services\Analytics;

use App\Services\ReporteModuloService;

class CajaAnalyticsService
{
    public function __construct(
        protected ReporteModuloService $reporteModuloService,
    ) {}

    public function getCajasReport(?string $fechaDesde, ?string $fechaHasta): array
    {
        return $this->reporteModuloService->datosReporteCajas($fechaDesde, $fechaHasta);
    }
}
