<?php

namespace App\Http\Controllers;

use App\Models\Core\Caja;
use App\Services\CajaService;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class CajaReporteController extends Controller
{
    public function entradasTicketPdf(Request $request, Caja $caja, CajaService $cajaService)
    {
        $this->authorize('caja.ver');

        $caja->loadMissing(['usuario', 'sucursal']);
        $data = $cajaService->obtenerReporteEntradasDetallado($caja);
        $html = view('cajas.reportes.entradas-ticket-pdf', $data)->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 240],
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 5,
            'margin_bottom' => 6,
        ]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', 'S');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket-entradas-caja-'.$caja->id.'.pdf"',
        ]);
    }
}
