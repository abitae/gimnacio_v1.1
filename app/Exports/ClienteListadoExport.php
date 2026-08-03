<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class ClienteListadoExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    /**
     * @param  Collection<int, \App\Models\Core\Cliente>  $clientes
     */
    public function __construct(
        protected Collection $clientes,
    ) {}

    public function collection()
    {
        return $this->clientes->map(function ($cliente) {
            $deuda = (float) $cliente->deuda_total;

            return [
                trim(($cliente->nombres ?? '').' '.($cliente->apellidos ?? '')),
                $cliente->codigo ?? '',
                $cliente->tipo_documento ?? '',
                $cliente->numero_documento ?? '',
                $cliente->email ?? '',
                $cliente->telefono ?? '',
                $cliente->asesor_nombre ?? '',
                ucfirst((string) ($cliente->estado_cliente ?? '')),
                $deuda > 0 ? $deuda : 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'Cliente',
            'Código',
            'Tipo documento',
            'Nº documento',
            'Email',
            'Teléfono',
            'Asesor',
            'Estado',
            'Deuda (S/)',
        ];
    }

    public function title(): string
    {
        return 'Listado clientes';
    }
}
