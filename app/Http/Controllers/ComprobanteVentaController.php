<?php

namespace App\Http\Controllers;

use App\Models\Core\Venta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Mpdf\Mpdf;

class ComprobanteVentaController extends Controller
{
    /**
     * Muestra el comprobante de una venta (HTML) para previsualizar e imprimir.
     */
    public function show(Request $request, Venta $venta)
    {
        if (! Gate::allows('pos.view')) {
            abort(403);
        }

        $venta->load(['items', 'cliente', 'employee', 'paymentMethod']);

        return view('ventas.comprobante', [
            'venta' => $venta,
        ]);
    }

    /**
     * Genera el PDF del comprobante con mPDF (para mostrar en modal).
     */
    public function pdf(Request $request, Venta $venta)
    {
        if (! Gate::allows('pos.view')) {
            abort(403);
        }

        $venta->load(['items', 'cliente', 'employee', 'paymentMethod']);

        $html = view('ventas.comprobante-pdf', ['venta' => $venta])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 297], // Ticket 80mm ancho (rollo térmico)
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 6,
            'margin_bottom' => 6,
        ]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', 'S');

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="comprobante-' . $venta->numero_venta . '.pdf"',
        ]);
    }
}
