<?php

namespace App\Exports;

use App\Models\Import;
use App\Models\ImportRow;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ImportErroresExport implements FromCollection, WithHeadings
{
    public function __construct(
        private readonly Import $import
    ) {}

    public function headings(): array
    {
        return [
            'fila',
            'estado',
            'errores',
            'codigo',
            'dni',
            'nombres',
        ];
    }

    public function collection(): Collection
    {
        return ImportRow::query()
            ->where('import_id', $this->import->id)
            ->orderBy('fila_numero')
            ->get()
            ->filter(function (ImportRow $row): bool {
                $errs = $row->errores_json ?? [];
                if ($row->estado === 'error') {
                    return true;
                }

                return is_array($errs) && count($errs) > 0;
            })
            ->map(function (ImportRow $row): array {
                $data = $row->data_json ?? [];
                $errs = $row->errores_json ?? ($data['errores'] ?? []);

                return [
                    'fila' => $row->fila_numero,
                    'estado' => $row->estado,
                    'errores' => is_array($errs) ? implode('; ', $errs) : '',
                    'codigo' => $data['codigo'] ?? '',
                    'dni' => $data['dni'] ?? '',
                    'nombres' => $data['nombres'] ?? '',
                ];
            });
    }
}
