<?php

namespace App\Http\Controllers;

use App\Notifications\ReporteModuloExportListo;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReporteExportDownloadController extends Controller
{
    public function __invoke(Request $request, string $exportRef): StreamedResponse
    {
        $this->authorize('reporte.ver');

        /** @var DatabaseNotification|null $row */
        $row = $request->user()
            ->notifications()
            ->where('type', ReporteModuloExportListo::class)
            ->orderByDesc('created_at')
            ->limit(100)
            ->get()
            ->first(fn (DatabaseNotification $n) => ($n->data['export_ref'] ?? '') === $exportRef);

        if ($row === null) {
            abort(404);
        }

        $path = $row->data['storage_path'] ?? null;
        if (! is_string($path) || $path === '') {
            abort(404);
        }

        $expectedPrefix = 'exports/reportes/'.$request->user()->id.'/';
        if (! str_starts_with($path, $expectedPrefix)) {
            abort(403);
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        $downloadName = $row->data['filename'] ?? basename($path);

        return Storage::disk('local')->download($path, $downloadName);
    }
}
