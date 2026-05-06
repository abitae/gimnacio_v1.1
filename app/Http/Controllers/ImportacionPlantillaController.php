<?php

namespace App\Http\Controllers;

use App\Exports\ImportPlantillaExport;
use App\Support\Imports\InitialLoadCatalog;
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

        if (! in_array($tipo, InitialLoadCatalog::implemented(), true)) {
            throw new NotFoundHttpException;
        }

        $config = InitialLoadCatalog::for($tipo);

        return Excel::download(
            new ImportPlantillaExport($config['sheet_title'], $config['title_row'], $config['headers']),
            $config['filename']
        );
    }
}
