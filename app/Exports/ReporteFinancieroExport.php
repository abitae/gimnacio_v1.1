<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteFinancieroExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function sheets(): array
    {
        return [
            new class($this->data['pagos'] ?? collect()) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $pagos) {}
                public function collection() {
                    return $this->pagos->map(fn ($p) => [
                        $p->fecha_pago ? $p->fecha_pago->format('d/m/Y H:i') : '',
                        $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '',
                        (float) $p->monto,
                        $p->moneda ?? 'PEN',
                        $p->metodo_pago ?? '',
                        $p->comprobante_tipo ?? '',
                        $p->comprobante_numero ?? '',
                    ]);
                }
                public function headings(): array { return ['Fecha', 'Cliente', 'Monto', 'Moneda', 'Método pago', 'Comprobante tipo', 'Comprobante número']; }
                public function title(): string { return 'Pagos'; }
            },
            new class($this->data['ventas'] ?? collect()) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $ventas) {}
                public function collection() {
                    return $this->ventas->map(fn ($v) => [
                        $v->fecha_venta ? $v->fecha_venta->format('d/m/Y H:i') : '',
                        $v->cliente ? trim($v->cliente->nombres . ' ' . $v->cliente->apellidos) : '',
                        (float) $v->total,
                        $v->metodo_pago ?? '',
                    ]);
                }
                public function headings(): array { return ['Fecha', 'Cliente', 'Total', 'Método pago']; }
                public function title(): string { return 'Ventas'; }
            },
        ];
    }
}
