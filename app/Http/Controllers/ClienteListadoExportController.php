<?php

namespace App\Http\Controllers;

use App\Exports\ClienteListadoExport;
use App\Services\ClienteService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ClienteListadoExportController extends Controller
{
    public function exportExcel(Request $request, ClienteService $service): BinaryFileResponse
    {
        $this->authorize('cliente.ver');

        $search = $request->string('search')->trim()->toString();
        $codigo = $request->string('codigo')->trim()->toString();
        $estado = $request->string('estado')->trim()->toString();
        $vigencia = $request->string('vigencia')->trim()->toString();
        $asesorId = $request->query('asesor_id') ? (int) $request->query('asesor_id') : null;
        $membresiaId = $request->query('membresia_id') ? (int) $request->query('membresia_id') : null;
        $ventanaDias = max(1, (int) $request->query('ventana_dias', 15));

        $clientes = $service->listForExport(
            $search,
            $estado !== '' ? $estado : null,
            $codigo !== '' ? $codigo : null,
            $asesorId,
            $vigencia !== '' ? $vigencia : null,
            $membresiaId,
            $ventanaDias,
        );

        return (new ClienteListadoExport($clientes))
            ->download('listado_clientes_'.now()->format('Y-m-d_His').'.xlsx');
    }
}
