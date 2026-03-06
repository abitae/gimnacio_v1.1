<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteUsuariosExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function collection()
    {
        $porUsuario = $this->data['por_usuario'] ?? collect();
        return $porUsuario->map(function ($row) {
            return [
                $row->usuario ? $row->usuario->name : 'Usuario #' . $row->usuario_id,
                $row->usuario ? $row->usuario->email : '',
                (int) $row->cantidad,
                (float) ($row->total_ventas ?? 0),
            ];
        });
    }

    public function headings(): array
    {
        return ['Usuario', 'Email', 'Cantidad ventas', 'Total vendido (S/)'];
    }

    public function title(): string
    {
        return 'Ventas por usuario';
    }
}
