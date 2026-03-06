<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteGimnasioExport implements FromArray, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function array(): array
    {
        $r = $this->data['resumen'] ?? [];
        return [
            ['Ventas total (período)', $r['ventas_total'] ?? 0],
            ['Cantidad ventas', $r['ventas_cantidad'] ?? 0],
            ['Matrículas nuevas', $r['matriculas_nuevas'] ?? 0],
            ['Ingresos matrículas', $r['ingresos_matriculas'] ?? 0],
            ['Ingresos totales', $r['ingresos_totales'] ?? 0],
            ['Clientes totales', $r['clientes_totales'] ?? 0],
            ['Clientes activos', $r['clientes_activos'] ?? 0],
            ['Membresías activas', $r['membresias_activas'] ?? 0],
        ];
    }

    public function headings(): array
    {
        return ['Concepto', 'Valor'];
    }

    public function title(): string
    {
        return 'Resumen Gimnasio';
    }
}
