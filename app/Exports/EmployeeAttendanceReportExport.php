<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeeAttendanceReportExport implements FromCollection, WithHeadings, WithTitle
{
    use Exportable;

    public function __construct(
        protected array $filasResumen,
        protected array $filasDetalle,
        protected array $totales,
        protected string $periodo,
    ) {}

    public function collection()
    {
        $rows = collect($this->filasResumen)->map(fn (array $fila) => [
            $fila['empleado'] ?? '',
            $fila['documento'] ?? '',
            $fila['cargo'] ?? '',
            (int) ($fila['dias'] ?? 0),
            (int) ($fila['tardanza_minutos'] ?? 0),
        ]);

        if ($this->totales !== []) {
            $rows->push([
                'TOTALES',
                '',
                '',
                (int) ($this->totales['total_dias'] ?? 0),
                (int) ($this->totales['total_tardanza_minutos'] ?? 0),
            ]);
        }

        $rows->push(['', '', '', '', '']);
        $rows->push(['DETALLE DIARIO', '', '', '', '']);
        $rows->push(['Empleado', 'Documento', 'Fecha', 'Ingreso', 'Salida', 'Tardanza (min)', 'Observaciones', 'Registrado por']);

        foreach ($this->filasDetalle as $fila) {
            $rows->push([
                $fila['empleado'] ?? '',
                $fila['documento'] ?? '',
                $fila['fecha'] ?? '',
                $fila['hora_ingreso'] ?? '',
                $fila['hora_salida'] ?? '',
                (int) ($fila['tardanza_minutos'] ?? 0),
                $fila['observaciones'] ?? '',
                $fila['registrado_por'] ?? '',
            ]);
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Empleado',
            'Documento',
            'Cargo',
            'Días registrados',
            'Tardanza (min)',
        ];
    }

    public function title(): string
    {
        return 'Asistencia '.$this->periodo;
    }
}
