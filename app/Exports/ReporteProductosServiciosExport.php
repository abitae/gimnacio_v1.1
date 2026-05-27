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
            new class($this->data['items_mas_vendidos'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $items) {}

                public function collection()
                {
                    return $this->items->map(fn ($i) => [
                        $i->tipo_item ?? '',
                        $i->nombre_item ?? '',
                        (int) ($i->cantidad_vendida ?? 0),
                        (float) ($i->total ?? 0),
                    ]);
                }

                public function headings(): array
                {
                    return ['Tipo', 'Nombre', 'Cantidad vendida', 'Total (S/)'];
                }

                public function title(): string
                {
                    return 'Mas vendidos';
                }
            },
            new class($this->data['productos_por_caja'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $rows) {}

                public function collection()
                {
                    return $this->rows->map(fn ($row) => [
                        $row['caja'] ?? '',
                        $row['usuario_caja'] ?? '',
                        (int) ($row['ventas_count'] ?? 0),
                        (int) ($row['cantidad_productos'] ?? 0),
                        (float) ($row['total'] ?? 0),
                        collect($row['metodos_pago'] ?? [])->map(fn ($total, $metodo) => $metodo.': S/ '.number_format((float) $total, 2))->implode(', '),
                    ]);
                }

                public function headings(): array
                {
                    return ['Caja', 'Usuario caja', 'Ventas', 'Cantidad productos', 'Total (S/)', 'Metodos de pago'];
                }

                public function title(): string
                {
                    return 'Por caja';
                }
            },
            new class($this->data['productos_por_usuario'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $rows) {}

                public function collection()
                {
                    return $this->rows->map(fn ($row) => [
                        $row['usuario'] ?? '',
                        (int) ($row['ventas_count'] ?? 0),
                        (int) ($row['cantidad_productos'] ?? 0),
                        (float) ($row['total'] ?? 0),
                    ]);
                }

                public function headings(): array
                {
                    return ['Usuario', 'Ventas', 'Cantidad productos', 'Total (S/)'];
                }

                public function title(): string
                {
                    return 'Por usuario';
                }
            },
            new class($this->data['detalle_productos_vendidos'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $rows) {}

                public function collection()
                {
                    return $this->rows->map(fn ($row) => [
                        $row['fecha']?->format('Y-m-d H:i:s') ?? '',
                        $row['caja'] ?? '',
                        $row['usuario_caja'] ?? '',
                        $row['vendedor'] ?? '',
                        $row['comprador'] ?? '',
                        $row['producto'] ?? '',
                        (int) ($row['cantidad'] ?? 0),
                        (float) ($row['precio_unitario'] ?? 0),
                        (float) ($row['subtotal'] ?? 0),
                        (float) ($row['total_venta'] ?? 0),
                        $row['comprobante'] ?? '',
                        $row['numero_venta'] ?? '',
                        $row['estado'] ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return ['Fecha', 'Caja', 'Usuario caja', 'Vendedor', 'Comprador', 'Producto', 'Cantidad', 'Precio unitario', 'Subtotal', 'Total venta', 'Comprobante', 'Numero venta', 'Estado'];
                }

                public function title(): string
                {
                    return 'Detalle productos';
                }
            },
            new class($this->data['productos_bajo_stock'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $productos) {}

                public function collection()
                {
                    return $this->productos->map(fn ($p) => [
                        $p->codigo ?? '',
                        $p->nombre ?? '',
                        (int) $p->stock_actual,
                        (int) $p->stock_minimo,
                    ]);
                }

                public function headings(): array
                {
                    return ['Codigo', 'Nombre', 'Stock actual', 'Stock minimo'];
                }

                public function title(): string
                {
                    return 'Stock bajo';
                }
            },
        ];
    }
}
