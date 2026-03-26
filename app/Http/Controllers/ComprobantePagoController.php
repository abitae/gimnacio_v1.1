<?php

namespace App\Http\Controllers;

use App\Models\Core\Pago;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Mpdf\Mpdf;

class ComprobantePagoController extends Controller
{
    /**
     * PDF tipo ticket (80 mm) para un cobro registrado como Pago.
     */
    public function pdf(Request $request, Pago $pago)
    {
        if (! Gate::any([
            'clientes.view',
            'cliente-matriculas.view',
            'reportes.view',
            'cajas.view',
            'pos.view',
        ])) {
            abort(403);
        }

        $pago->load([
            'cliente',
            'clienteMatricula.membresia',
            'clienteMatricula.clase',
            'clienteMembresia.membresia',
            'registradoPor',
            'paymentMethod',
        ]);

        $html = view('pagos.comprobante-pago-pdf', ['pago' => $pago])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [80, 297],
            'margin_left' => 4,
            'margin_right' => 4,
            'margin_top' => 6,
            'margin_bottom' => 6,
        ]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', 'S');

        $num = $pago->comprobante_numero ?: (string) $pago->id;

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="ticket-cobro-'.$num.'.pdf"',
        ]);
    }
}
