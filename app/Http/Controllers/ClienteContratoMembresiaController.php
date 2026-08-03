<?php

namespace App\Http\Controllers;

use App\Models\Core\Cliente;
use App\Services\ClienteContratoMembresiaService;
use Illuminate\Http\Request;
use Mpdf\Mpdf;

class ClienteContratoMembresiaController extends Controller
{
    public function __construct(
        protected ClienteContratoMembresiaService $contratoService,
    ) {}

    public function pdf(Request $request, Cliente $cliente)
    {
        $this->authorize('cliente.ver');

        $contrato = $this->contratoService->datosContrato($cliente);
        $html = view('clientes.contrato-membresia-pdf', compact('contrato'))->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 14,
            'margin_right' => 14,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);
        $mpdf->WriteHTML($html);
        $pdf = $mpdf->Output('', 'S');

        $filename = 'contrato-membresia-'.($cliente->codigo ?: $cliente->id).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }
}
