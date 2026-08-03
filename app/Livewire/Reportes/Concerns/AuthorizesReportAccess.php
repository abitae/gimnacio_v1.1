<?php

declare(strict_types=1);

namespace App\Livewire\Reportes\Concerns;

use App\Support\ReporteCatalog;

trait AuthorizesReportAccess
{
    protected function authorizeReport(string $slug): void
    {
        ReporteCatalog::authorize($slug);
    }
}
