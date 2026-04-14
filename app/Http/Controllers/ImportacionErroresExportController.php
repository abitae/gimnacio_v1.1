<?php

namespace App\Http\Controllers;

use App\Exports\ImportErroresExport;
use App\Models\Import;
use Illuminate\Support\Facades\Gate;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ImportacionErroresExportController extends Controller
{
    public function excel(Import $import): BinaryFileResponse
    {
        if (! Gate::allows('importacion.ver')) {
            abort(403);
        }

        $filename = 'import-'.$import->id.'-errores.xlsx';

        return Excel::download(new ImportErroresExport($import), $filename);
    }
}
