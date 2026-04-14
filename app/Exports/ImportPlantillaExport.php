<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ImportPlantillaExport implements FromArray, WithTitle
{
    /**
     * @param  list<string>  $headers
     */
    public function __construct(
        private readonly string $sheetTitle,
        private readonly string $titleRow,
        private readonly array $headers
    ) {}

    public function array(): array
    {
        return [
            [$this->titleRow],
            $this->headers,
        ];
    }

    public function title(): string
    {
        return mb_substr(preg_replace('/[^\p{L}\p{N}\s_-]/u', '', $this->sheetTitle) ?: 'Plantilla', 0, 31);
    }
}
