<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteMatriculasExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function collection()
    {
        $matriculas = $this->data['matriculas'] ?? collect();
        return $matriculas->map(function ($m) {
            return [
                $m->fecha_matricula ? $m->fecha_matricula->format('d/m/Y') : '',
                $m->fecha_inicio ? $m->fecha_inicio->format('d/m/Y') : '',
                $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '',
                $m->cliente ? ($m->cliente->tipo_documento ?? '') . ' ' . ($m->cliente->numero_documento ?? '') : '',
                $m->tipo ?? '',
                $m->nombre,
                (float) ($m->precio_lista ?? 0),
                (float) ($m->descuento_monto ?? 0),
                (float) ($m->precio_final ?? 0),
                $m->estado ?? '',
                $m->canal_venta ?? '',
                $m->asesor ? $m->asesor->name : '',
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Fecha matrícula',
            'Fecha inicio',
            'Cliente',
            'Documento',
            'Tipo',
            'Producto/Servicio',
            'Precio lista',
            'Descuento',
            'Precio final',
            'Estado',
            'Canal venta',
            'Asesor',
        ];
    }

    public function title(): string
    {
        return 'Matrículas';
    }
}
