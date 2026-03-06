<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteCajasExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function collection()
    {
        $cajas = $this->data['cajas'] ?? collect();
        return $cajas->map(function ($c) {
            return [
                $c->id,
                $c->usuario ? $c->usuario->name : '',
                $c->fecha_apertura ? $c->fecha_apertura->format('d/m/Y H:i') : '',
                $c->fecha_cierre ? $c->fecha_cierre->format('d/m/Y H:i') : '',
                (float) $c->saldo_inicial,
                (float) ($c->saldo_final ?? 0),
                $c->estado ?? '',
            ];
        });
    }

    public function headings(): array
    {
        return ['# Caja', 'Usuario', 'Fecha apertura', 'Fecha cierre', 'Saldo inicial', 'Saldo final', 'Estado'];
    }

    public function title(): string
    {
        return 'Cajas';
    }
}
