<?php

namespace App\Http\Controllers;

use App\Models\Core\Cliente;
use App\Services\ClienteContratoMembresiaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ClienteContratoMembresiaController extends Controller
{
    public function __construct(
        protected ClienteContratoMembresiaService $contratoService,
    ) {}

    public function pdf(Request $request, Cliente $cliente): Response
    {
        $this->authorize('cliente.ver');

        return $this->contratoService->respuestaPreview($cliente);
    }

    public function descargarFirmado(Cliente $cliente): Response
    {
        return $this->contratoService->respuestaDescarga($cliente);
    }
}
