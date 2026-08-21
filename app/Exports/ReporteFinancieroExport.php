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
        $resumen = $this->data['resumen'] ?? [];
        $matriz = $resumen['matriz_tipo_metodo'] ?? [];

        return [
            new class($resumen, $matriz) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected array $resumen, protected array $matriz) {}

                public function collection()
                {
                    $rows = collect([
                        ['Cobros', (float) ($this->resumen['total_pagos'] ?? 0), (int) ($this->resumen['cantidad_pagos'] ?? 0)],
                        ['Ventas cobradas', (float) ($this->resumen['ventas_cobradas'] ?? 0), (int) ($this->resumen['cantidad_ventas'] ?? 0)],
                        ['Ventas facturadas', (float) ($this->resumen['total_ventas'] ?? 0), ''],
                        ['Saldo a crédito', (float) ($this->resumen['saldo_credito'] ?? 0), ''],
                        ['Ingresos reales', (float) ($this->resumen['ingresos_totales'] ?? 0), ''],
                    ]);

                    foreach ($this->matriz['tipos'] ?? [] as $tipo) {
                        $rows->push([
                            'Tipo: '.$tipo,
                            (float) ($this->matriz['totales_tipo'][$tipo] ?? 0),
                            '',
                        ]);
                    }

                    return $rows;
                }

                public function headings(): array
                {
                    return ['Concepto', 'Monto', 'Cantidad'];
                }

                public function title(): string
                {
                    return 'Resumen';
                }
            },
            new class($this->data['pagos'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $pagos) {}

                public function collection()
                {
                    return $this->pagos->map(fn ($p) => [
                        $p->fecha_pago ? $p->fecha_pago->format('d/m/Y H:i') : '',
                        $p->cliente ? trim($p->cliente->nombres.' '.$p->cliente->apellidos) : '',
                        $p->etiquetaOrigen(),
                        (float) $p->monto,
                        $p->moneda ?? 'PEN',
                        method_exists($p, 'metodosPagoResumen') ? $p->metodosPagoResumen() : ($p->metodo_pago ?? ''),
                        $p->comprobante_tipo ?? '',
                        $p->comprobante_numero ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return ['Fecha', 'Cliente', 'Tipo', 'Monto', 'Moneda', 'Método pago', 'Comprobante tipo', 'Comprobante número'];
                }

                public function title(): string
                {
                    return 'Pagos';
                }
            },
            new class($this->data['ventas'] ?? collect()) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $ventas) {}

                public function collection()
                {
                    return $this->ventas->map(fn ($v) => [
                        $v->fecha_venta ? $v->fecha_venta->format('d/m/Y H:i') : '',
                        $v->numero_venta ?? $v->id,
                        $v->cliente ? trim($v->cliente->nombres.' '.$v->cliente->apellidos) : '',
                        (float) $v->total,
                        method_exists($v, 'metodosPagoResumen') ? $v->metodosPagoResumen() : ($v->metodo_pago ?? ''),
                        $v->estado ?? '',
                        ! empty($v->es_credito) ? 'Sí' : 'No',
                    ]);
                }

                public function headings(): array
                {
                    return ['Fecha', 'Nº venta', 'Cliente', 'Total', 'Método pago', 'Estado', 'Crédito'];
                }

                public function title(): string
                {
                    return 'Ventas';
                }
            },
        ];
    }
}
