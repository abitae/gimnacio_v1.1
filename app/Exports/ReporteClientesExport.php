<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteClientesExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function collection()
    {
        $clientes = $this->data['clientes'] ?? collect();
        return $clientes->map(function ($c) {
            return [
                $c->tipo_documento ?? '',
                $c->numero_documento ?? '',
                $c->nombres ?? '',
                $c->apellidos ?? '',
                $c->telefono ?? '',
                $c->email ?? '',
                $c->direccion ?? '',
                $c->estado_cliente ?? '',
                $c->cliente_membresias_count ?? 0,
                $c->pagos_count ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Tipo documento',
            'Nº Documento',
            'Nombres',
            'Apellidos',
            'Teléfono',
            'Email',
            'Dirección',
            'Estado',
            'Membresías',
            'Pagos',
        ];
    }

    public function title(): string
    {
        return 'Clientes';
    }
}
