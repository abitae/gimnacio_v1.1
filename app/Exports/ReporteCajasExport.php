<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReporteCajasExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        protected array $data
    ) {}

    public function sheets(): array
    {
        $resumen = $this->data['resumen'] ?? [];
        $matriz = $resumen['matriz_tipo_metodo'] ?? [];
        $porCaja = collect($this->data['por_caja'] ?? []);
        $porUsuario = collect($resumen['por_usuario'] ?? []);
        $ventasCredito = collect($this->data['ventas_credito'] ?? []);
        $movimientos = collect($this->data['detalle_movimientos'] ?? []);

        return [
            new class($resumen) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected array $resumen) {}

                public function collection()
                {
                    $credito = $this->resumen['ventas_credito'] ?? [];

                    return collect([
                        ['Cajas registradas', (int) ($this->resumen['cantidad'] ?? 0)],
                        ['Cajas abiertas', (int) ($this->resumen['abiertas'] ?? 0)],
                        ['Cajas cerradas', (int) ($this->resumen['cerradas'] ?? 0)],
                        ['Total ingresos caja (efectivo/real)', (float) ($this->resumen['total_ingresos'] ?? 0)],
                        ['Total salidas', (float) ($this->resumen['total_salidas'] ?? 0)],
                        ['Total vendido POS (sin credito ficticio)', (float) ($this->resumen['total_vendido'] ?? 0)],
                        ['Ventas al credito (cantidad)', (int) ($credito['cantidad'] ?? 0)],
                        ['Ventas al credito (total)', (float) ($credito['total_ventas'] ?? 0)],
                        ['Ventas al credito (anticipos)', (float) ($credito['total_anticipos'] ?? 0)],
                        ['Ventas al credito (saldo pendiente)', (float) ($credito['total_saldo_pendiente'] ?? 0)],
                    ]);
                }

                public function headings(): array
                {
                    return ['Concepto', 'Valor'];
                }

                public function title(): string
                {
                    return 'Resumen';
                }
            },
            new class($this->data['cajas'] ?? collect(), $porCaja) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected $cajas, protected Collection $porCaja) {}

                public function collection()
                {
                    $porCajaMap = $this->porCaja->keyBy('caja_id');

                    return $this->cajas->map(function ($c) use ($porCajaMap) {
                        $fila = $porCajaMap->get($c->id, []);

                        return [
                            $c->id,
                            $c->usuario ? $c->usuario->name : '',
                            $c->sucursal?->nombre ?? '',
                            $c->fecha_apertura ? $c->fecha_apertura->format('d/m/Y H:i') : '',
                            $c->fecha_cierre ? $c->fecha_cierre->format('d/m/Y H:i') : '',
                            $c->estado ?? '',
                            (float) $c->saldo_inicial,
                            (float) ($fila['total_ingresos'] ?? 0),
                            (float) ($fila['total_salidas'] ?? 0),
                            (float) ($c->saldo_final ?? 0),
                        ];
                    });
                }

                public function headings(): array
                {
                    return [
                        '# Caja',
                        'Usuario',
                        'Sucursal',
                        'Fecha apertura',
                        'Fecha cierre',
                        'Estado',
                        'Saldo inicial',
                        'Ingresos caja',
                        'Salidas',
                        'Saldo final',
                    ];
                }

                public function title(): string
                {
                    return 'Cajas';
                }
            },
            new class($porUsuario) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected Collection $porUsuario) {}

                public function collection()
                {
                    return $this->porUsuario->map(fn (array $row) => [
                        $row['usuario'] ?? '',
                        (int) ($row['cantidad_cajas'] ?? 0),
                        (float) ($row['total_ingresos'] ?? 0),
                        (float) ($row['total_salidas'] ?? 0),
                        (float) ($row['ventas_credito_total'] ?? 0),
                        (float) ($row['saldo_credito_pendiente'] ?? 0),
                    ]);
                }

                public function headings(): array
                {
                    return [
                        'Usuario',
                        'Cantidad cajas',
                        'Ingresos caja (S/)',
                        'Salidas (S/)',
                        'Ventas credito (S/)',
                        'Saldo credito pendiente (S/)',
                    ];
                }

                public function title(): string
                {
                    return 'Por usuario';
                }
            },
            new class($matriz) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected array $matriz) {}

                public function collection()
                {
                    $tipos = $this->matriz['tipos'] ?? [];
                    $metodos = $this->matriz['metodos'] ?? [];
                    $celdas = $this->matriz['celdas'] ?? [];
                    $totalesTipo = $this->matriz['totales_tipo'] ?? [];
                    $totalesMetodo = $this->matriz['totales_metodo'] ?? [];

                    $filas = collect($tipos)->map(function (string $tipo) use ($metodos, $celdas, $totalesTipo) {
                        $row = [$tipo];
                        foreach ($metodos as $metodo) {
                            $row[] = (float) ($celdas[$tipo][$metodo]['total'] ?? 0);
                        }
                        $row[] = (float) ($totalesTipo[$tipo] ?? 0);

                        return $row;
                    });

                    if ($metodos !== []) {
                        $totalRow = ['Total'];
                        foreach ($metodos as $metodo) {
                            $totalRow[] = (float) ($totalesMetodo[$metodo] ?? 0);
                        }
                        $totalRow[] = (float) ($this->matriz['total_general'] ?? 0);
                        $filas->push($totalRow);
                    }

                    return $filas;
                }

                public function headings(): array
                {
                    $metodos = $this->matriz['metodos'] ?? [];
                    if ($metodos === []) {
                        return ['Tipo'];
                    }

                    return array_merge(['Tipo'], $metodos, ['Total']);
                }

                public function title(): string
                {
                    return 'Tipo x Metodo';
                }
            },
            new class($ventasCredito) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected Collection $ventasCredito) {}

                public function collection()
                {
                    return $this->ventasCredito->map(fn (array $row) => [
                        $row['numero_venta'] ?? '',
                        $row['fecha']?->format('d/m/Y H:i') ?? '',
                        $row['caja_id'] ?? '',
                        $row['usuario_caja'] ?? '',
                        $row['vendedor'] ?? '',
                        $row['comprador'] ?? '',
                        (float) ($row['total'] ?? 0),
                        (float) ($row['monto_inicial'] ?? 0),
                        (float) ($row['saldo_pendiente'] ?? 0),
                        $row['fecha_vencimiento'] ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return [
                        'Numero venta',
                        'Fecha',
                        'Caja',
                        'Usuario caja',
                        'Vendedor',
                        'Comprador',
                        'Total (S/)',
                        'Anticipo (S/)',
                        'Saldo pendiente (S/)',
                        'Vencimiento',
                    ];
                }

                public function title(): string
                {
                    return 'Ventas credito';
                }
            },
            new class($movimientos) implements FromCollection, WithHeadings, WithTitle
            {
                public function __construct(protected Collection $movimientos) {}

                public function collection()
                {
                    return $this->movimientos->map(fn (array $row) => [
                        $row['fecha']?->format('d/m/Y H:i') ?? '',
                        $row['caja_id'] ?? '',
                        $row['usuario_caja'] ?? ($row['usuario'] ?? ''),
                        $row['sucursal_caja'] ?? ($row['sucursal'] ?? ''),
                        $row['concepto'] ?? '',
                        $row['tipo'] ?? '',
                        $row['metodo_pago'] ?? '',
                        (float) ($row['monto'] ?? 0),
                        ! empty($row['excluir_totales_caja']) ? 'Si' : 'No',
                        ! empty($row['es_venta_credito']) ? 'Si' : 'No',
                        $row['referencia_label'] ?? '',
                    ]);
                }

                public function headings(): array
                {
                    return [
                        'Fecha',
                        'Caja',
                        'Usuario caja',
                        'Sucursal',
                        'Concepto',
                        'Tipo',
                        'Metodo pago',
                        'Monto (S/)',
                        'Excluido de totales caja',
                        'Venta credito',
                        'Referencia',
                    ];
                }

                public function title(): string
                {
                    return 'Movimientos';
                }
            },
        ];
    }
}
