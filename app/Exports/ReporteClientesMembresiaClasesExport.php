<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteClientesMembresiaClasesExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function sheets(): array
    {
        $membresias = $this->data['membresias_activas'] ?? collect();
        $matriculasMembresia = $this->data['matriculas_membresia_activas'] ?? collect();
        $matriculasClase = $this->data['matriculas_clase_activas'] ?? collect();
        $pagosMembresia = $this->data['pagos_membresia'] ?? collect();
        $pagosClase = $this->data['pagos_clase'] ?? collect();

        return [
            new class($membresias, $matriculasMembresia) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(
                    protected $membresias,
                    protected $matriculasMembresia
                ) {}

                public function collection()
                {
                    $rows = $this->membresias->map(fn ($m) => [
                        $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '-',
                        $m->membresia?->nombre ?? 'N/A',
                        $m->fecha_matricula?->format('d/m/Y') ?? '-',
                        $m->fecha_inicio?->format('d/m/Y'),
                        $m->fecha_fin?->format('d/m/Y') ?? '-',
                        (float) ($m->precio_final ?? 0),
                        'Membresía',
                    ]);
                    return $rows->concat($this->matriculasMembresia->map(fn ($m) => [
                        $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '-',
                        $m->nombre,
                        $m->fecha_matricula?->format('d/m/Y') ?? '-',
                        $m->fecha_inicio?->format('d/m/Y'),
                        $m->fecha_fin?->format('d/m/Y') ?? '-',
                        (float) ($m->precio_final ?? 0),
                        'Matrícula membresía',
                    ]));
                }

                public function headings(): array
                {
                    return ['Cliente', 'Membresía / Producto', 'Matrícula', 'Inicio', 'Fin', 'Precio final', 'Origen'];
                }

                public function title(): string
                {
                    return 'Membresías activas';
                }
            },
            new class($matriculasClase) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $matriculasClase) {}

                public function collection()
                {
                    return $this->matriculasClase->map(fn ($m) => [
                        $m->cliente ? trim($m->cliente->nombres . ' ' . $m->cliente->apellidos) : '-',
                        $m->nombre,
                        $m->fecha_matricula?->format('d/m/Y') ?? '-',
                        $m->fecha_inicio?->format('d/m/Y'),
                        $m->fecha_fin?->format('d/m/Y') ?? '-',
                        (float) ($m->precio_final ?? 0),
                    ]);
                }

                public function headings(): array
                {
                    return ['Cliente', 'Clase', 'Matrícula', 'Inicio', 'Fin', 'Precio final'];
                }

                public function title(): string
                {
                    return 'Clases activas';
                }
            },
            new class($pagosMembresia) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $pagosMembresia) {}

                public function collection()
                {
                    return $this->pagosMembresia->map(fn ($p) => [
                        $p->fecha_pago?->format('d/m/Y H:i'),
                        $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-',
                        $p->clienteMembresia?->membresia?->nombre ?? '-',
                        (float) $p->monto,
                        $p->metodo_pago ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return ['Fecha', 'Cliente', 'Membresía', 'Monto', 'Método pago'];
                }

                public function title(): string
                {
                    return 'Pagos membresía';
                }
            },
            new class($pagosClase) implements FromCollection, WithHeadings, WithTitle {
                public function __construct(protected $pagosClase) {}

                public function collection()
                {
                    return $this->pagosClase->map(fn ($p) => [
                        $p->fecha_pago?->format('d/m/Y H:i'),
                        $p->cliente ? trim($p->cliente->nombres . ' ' . $p->cliente->apellidos) : '-',
                        $p->clienteMatricula?->nombre ?? '-',
                        (float) $p->monto,
                        $p->metodo_pago ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return ['Fecha', 'Cliente', 'Clase', 'Monto', 'Método pago'];
                }

                public function title(): string
                {
                    return 'Pagos clases';
                }
            },
        ];
    }
}
