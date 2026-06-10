<?php

namespace App\Livewire\Reportes\Concerns;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

trait PaginatesReportTables
{
    protected function paginateReportCollection($items, int $perPage, string $pageName): LengthAwarePaginator
    {
        $collection = $items instanceof Collection ? $items->values() : collect($items)->values();
        $page = max(1, (int) $this->getPage($pageName));
        $perPage = max(1, $perPage);

        return new LengthAwarePaginator(
            $collection->forPage($page, $perPage)->values(),
            $collection->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'pageName' => $pageName,
            ]
        );
    }

    protected function resetReportPages(array $pageNames): void
    {
        foreach ($pageNames as $pageName) {
            $this->resetPage($pageName);
        }
    }
}
