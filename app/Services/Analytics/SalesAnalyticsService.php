<?php

namespace App\Services\Analytics;

use App\Services\ReporteModuloService;

class SalesAnalyticsService
{
    public function __construct(
        protected ReporteModuloService $reporteModuloService,
    ) {}

    public function getVentasReport(?string $fechaDesde, ?string $fechaHasta): array
    {
        return $this->reporteModuloService->datosReporteVentas($fechaDesde, $fechaHasta);
    }
}
