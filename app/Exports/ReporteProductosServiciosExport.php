<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteProductosServiciosExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function sheets(): array
    {
        return [
            new class($this->data['items_mas_vendidos'] ?? collect()) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $items) {}
                public function collection() {
                    return $this->items->map(fn ($i) => [
                        $i->tipo_item ?? '',
                        $i->nombre_item ?? '',
                        (int) ($i->cantidad_vendida ?? 0),
                        (float) ($i->total ?? 0),
                    ]);
                }
                public function headings(): array { return ['Tipo', 'Nombre', 'Cantidad vendida', 'Total (S/)']; }
                public function title(): string { return 'Más vendidos'; }
            },
            new class($this->data['productos_bajo_stock'] ?? collect()) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $productos) {}
                public function collection() {
                    return $this->productos->map(fn ($p) => [
                        $p->codigo ?? '',
                        $p->nombre ?? '',
                        (int) $p->stock_actual,
                        (int) $p->stock_minimo,
                    ]);
                }
                public function headings(): array { return ['Código', 'Nombre', 'Stock actual', 'Stock mínimo']; }
                public function title(): string { return 'Stock bajo'; }
            },
        ];
    }
}
