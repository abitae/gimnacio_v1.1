<?php

namespace App\Http\Controllers;

use App\Exports\CreditSalesExport;
use App\Services\CreditSalesQueryService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CreditSalesExportController extends Controller
{
    public function exportExcel(Request $request, CreditSalesQueryService $queryService): BinaryFileResponse
    {
        $this->authorize('punto_venta.ver');

        $search = $request->string('search')->trim()->toString();
        $fechaInicio = $request->string('fecha_inicio')->trim()->toString();
        $fechaFin = $request->string('fecha_fin')->trim()->toString();

        $query = $queryService->query(
            $search !== '' ? $search : null,
            $fechaInicio !== '' ? $fechaInicio : null,
            $fechaFin !== '' ? $fechaFin : null,
        );
        $totales = $queryService->totales($query);
        $filas = $queryService->filasParaExport(
            $search !== '' ? $search : null,
            $fechaInicio !== '' ? $fechaInicio : null,
            $fechaFin !== '' ? $fechaFin : null,
        );

        return (new CreditSalesExport($filas, $totales))
            ->download('ventas_credito_'.now()->format('Y-m-d_His').'.xlsx');
    }
}
