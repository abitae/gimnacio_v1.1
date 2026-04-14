<?php

namespace App\Http\Controllers;

use App\Exports\ImportPlantillaExport;
use App\Services\Imports\ExcelCuotasLegacyReader;
use App\Services\Imports\ExcelDeudasReader;
use App\Services\Imports\ExcelSociosReader;
use App\Support\Imports\ImportType;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImportacionPlantillaController extends Controller
{
    public function download(string $tipo): BinaryFileResponse
    {
        if (! Gate::allows('importacion.ver')) {
            abort(403);
        }

        if (! in_array($tipo, ImportType::implemented(), true)) {
            throw new NotFoundHttpException;
        }

        [$sheetTitle, $titleRow, $headers, $filename] = match ($tipo) {
            ImportType::USUARIOS => [
                'Vendedores',
                'Cualquier Excel legacy con columna VENDEDOR en la segunda fila de encabezados.',
                ['VENDEDOR'],
                'plantilla-vendedores-columna-vendedor.xlsx',
            ],
            ImportType::CLIENTES => [
                'Clientes',
                'Socios activos — primera fila título; segunda fila encabezados (exportación legacy).',
                ExcelSociosReader::EXPECTED_HEADERS,
                'plantilla-clientes-socios-activos.xlsx',
            ],
            ImportType::MEMBRESIAS_MATRICULAS => [
                'Membresias',
                'Mismo Excel que clientes; solo se importan filas de venta membresía.',
                ExcelSociosReader::EXPECTED_HEADERS,
                'plantilla-membresias-socios-activos.xlsx',
            ],
            ImportType::DEUDAS => [
                'Deudas',
                'Deudas clientes — primera fila título; segunda fila encabezados.',
                ExcelDeudasReader::EXPECTED_HEADERS,
                'plantilla-deudas-clientes.xlsx',
            ],
            ImportType::CUOTAS => [
                'Cuotas',
                'Deudas cuotas clientes — primera fila título; segunda fila encabezados.',
                ExcelCuotasLegacyReader::EXPECTED_HEADERS,
                'plantilla-cuotas.xlsx',
            ],
            default => throw new NotFoundHttpException,
        };

        return Excel::download(
            new ImportPlantillaExport($sheetTitle, $titleRow, $headers),
            $filename
        );
    }
}
