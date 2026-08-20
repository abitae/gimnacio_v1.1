<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BioTime\BioTimeBridgePackageService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BioTimeBridgeDownloadController extends Controller
{
    public function __invoke(BioTimeBridgePackageService $package): BinaryFileResponse|StreamedResponse|RedirectResponse
    {
        $this->authorize('biotime.ver');

        try {
            $zip = $package->buildZip();
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return response()
            ->download($zip['path'], $zip['filename'], [
                'Content-Type' => 'application/zip',
            ])
            ->deleteFileAfterSend(true);
    }
}
