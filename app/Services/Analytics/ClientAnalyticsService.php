<?php

namespace App\Services\Analytics;

use App\Services\ReporteModuloService;

class ClientAnalyticsService
{
    public function __construct(
        protected ReporteModuloService $reporteModuloService,
    ) {}

    public function getClientesReport(?string $fechaDesde, ?string $fechaHasta): array
    {
        return $this->reporteModuloService->datosReporteClientes($fechaDesde, $fechaHasta);
    }

    public function getMembresiaClasesReport(?string $fechaDesde, ?string $fechaHasta): array
    {
        return $this->reporteModuloService->datosReporteClientesMembresiaClasesActivas($fechaDesde, $fechaHasta);
    }
}
