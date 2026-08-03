<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CreditSalesExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $filas,
        protected array $totales = [],
    ) {}

    public function collection()
    {
        $rows = collect($this->filas)->map(fn (array $fila) => [
            $fila['codigo'] ?? '',
            $fila['comprador_nombre'] ?? '',
            $fila['comprador_tipo'] ?? '',
            $fila['comprador_detalle'] ?? '',
            $fila['numero_venta'] ?? '',
            (float) ($fila['total'] ?? 0),
            (float) ($fila['monto_pagado'] ?? 0),
            (float) ($fila['saldo'] ?? 0),
            isset($fila['fecha']) && $fila['fecha'] ? $fila['fecha']->format('d/m/Y H:i') : '',
            $fila['registrado_por'] ?? '',
            ucfirst((string) ($fila['estado'] ?? '')),
            $fila['fecha_vencimiento'] ?? '',
        ]);

        if ($this->totales !== []) {
            $rows->push([
                '',
                '',
                '',
                '',
                'TOTALES',
                (float) ($this->totales['total_ventas'] ?? 0),
                (float) ($this->totales['total_pagado'] ?? 0),
                (float) ($this->totales['total_saldo_pendiente'] ?? 0),
                '',
                '',
                '',
                '',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Codigo',
            'Cliente',
            'Tipo',
            'Documento / telefono',
            'N venta',
            'Total (S/)',
            'Pagado (S/)',
            'Saldo (S/)',
            'Fecha venta',
            'Registro',
            'Estado',
            'Vencimiento',
        ];
    }

    public function title(): string
    {
        return 'Ventas credito';
    }
}
