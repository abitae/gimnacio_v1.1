<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteVentasExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function collection()
    {
        $ventas = $this->data['ventas'] ?? collect();
        return $ventas->map(function ($v) {
            return [
                $v->fecha_venta ? $v->fecha_venta->format('d/m/Y H:i') : '',
                $v->numero_venta ?? $v->id,
                $v->cliente ? trim($v->cliente->nombres . ' ' . $v->cliente->apellidos) : '',
                $v->cliente ? ($v->cliente->tipo_documento ?? '') . ' ' . ($v->cliente->numero_documento ?? '') : '',
                (float) ($v->subtotal ?? 0),
                (float) ($v->descuento ?? 0),
                (float) ($v->igv ?? 0),
                (float) $v->total,
                $v->metodo_pago ?? '',
                $v->estado ?? '',
                $v->tipo_comprobante ?? '',
                $v->numero_comprobante ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Fecha/Hora',
            'Nº Venta',
            'Cliente',
            'Documento',
            'Subtotal',
            'Descuento',
            'IGV',
            'Total',
            'Método pago',
            'Estado',
            'Tipo comprobante',
            'Nº Comprobante',
        ];
    }

    public function title(): string
    {
        return 'Ventas';
    }
}
