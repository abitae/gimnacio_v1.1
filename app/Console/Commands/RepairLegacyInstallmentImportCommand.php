<?php

namespace App\Console\Commands;

use App\Models\Core\ClientDebt;
use App\Models\Core\ClienteMatricula;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairLegacyInstallmentImportCommand extends Command
{
    protected $signature = 'import:repair-legacy-cuotas {--execute : Aplica los cambios en base de datos}';

    protected $description = 'Repara cuotas legacy importadas, reasignando estados y reactivando modalidad cuotas en matriculas vinculadas.';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');
        $tolerance = 0.02;

        $debts = ClientDebt::query()
            ->where('tipo_deuda', 'cuotificada')
            ->where('observaciones', 'like', 'Import Excel legacy%')
            ->with(['enrollmentInstallments' => fn ($query) => $query
                ->orderBy('fecha_vencimiento')
                ->orderBy('numero_cuota')
                ->orderBy('id')])
            ->get();

        $summary = [
            'evaluadas' => 0,
            'reparables' => 0,
            'reparadas' => 0,
            'reparadas_por_inicial_separada' => 0,
            'reparadas_por_pago_aplicado_a_cuotas' => 0,
            'omitidas_con_pagos_reales' => 0,
            'omitidas_sin_cuotas' => 0,
            'omitidas_pago_no_alineado' => 0,
        ];

        foreach ($debts as $debt) {
            $summary['evaluadas']++;

            $installments = $debt->enrollmentInstallments;
            if ($installments->isEmpty()) {
                $summary['omitidas_sin_cuotas']++;
                continue;
            }

            if ($installments->contains(fn ($installment) => $installment->pago_id !== null)) {
                $summary['omitidas_con_pagos_reales']++;
                continue;
            }

            $sumCuotas = round((float) $installments->sum('monto'), 2);
            $montoTotal = round((float) $debt->monto_total, 2);
            $remaining = round((float) $debt->monto_pagado, 2);
            $saldo = round((float) $debt->saldo_pendiente, 2);
            $isInitialSeparated = abs($sumCuotas - $saldo) <= $tolerance
                && abs(($sumCuotas + $remaining) - $montoTotal) <= $tolerance;

            if ($isInitialSeparated) {
                $summary['reparables']++;

                if (! $execute) {
                    continue;
                }

                DB::transaction(function () use ($debt, $installments, $remaining, $sumCuotas): void {
                    foreach ($installments as $installment) {
                        $installment->update([
                            'estado' => $installment->fecha_vencimiento && $installment->fecha_vencimiento->isPast() ? 'vencida' : 'pendiente',
                            'fecha_pago' => null,
                        ]);
                    }

                    $matriculaIds = $installments
                        ->pluck('cliente_matricula_id')
                        ->filter()
                        ->unique()
                        ->values();

                    foreach ($matriculaIds as $matriculaId) {
                        $matricula = ClienteMatricula::query()->find($matriculaId);
                        if (! $matricula) {
                            continue;
                        }

                        $matricula->update([
                            'precio_lista' => (float) $debt->monto_total,
                            'precio_final' => (float) $debt->monto_total,
                            'modalidad_pago' => 'cuotas',
                            'requiere_plan_cuotas' => true,
                            'cuota_inicial_monto' => $remaining,
                        ]);
                    }
                });

                $summary['reparadas']++;
                $summary['reparadas_por_inicial_separada']++;
                continue;
            }

            $plan = [];
            $paymentAligned = true;

            foreach ($installments as $installment) {
                $monto = round((float) $installment->monto, 2);

                if ($remaining >= $monto - $tolerance) {
                    $plan[] = [
                        'id' => $installment->id,
                        'estado' => 'pagada',
                        'fecha_pago' => $installment->fecha_pago?->toDateString() ?? $installment->fecha_vencimiento?->toDateString(),
                    ];
                    $remaining = round(max(0, $remaining - $monto), 2);
                    continue;
                }

                if ($remaining > $tolerance) {
                    $paymentAligned = false;
                    break;
                }

                $plan[] = [
                    'id' => $installment->id,
                    'estado' => $installment->fecha_vencimiento && $installment->fecha_vencimiento->isPast() ? 'vencida' : 'pendiente',
                    'fecha_pago' => null,
                ];
            }

            if (! $paymentAligned) {
                $summary['omitidas_pago_no_alineado']++;
                continue;
            }

            $summary['reparables']++;

            if (! $execute) {
                continue;
            }

            DB::transaction(function () use ($debt, $installments, $plan): void {
                foreach ($plan as $row) {
                    $installments->firstWhere('id', $row['id'])?->update([
                        'estado' => $row['estado'],
                        'fecha_pago' => $row['fecha_pago'],
                    ]);
                }

                $matriculaIds = $installments
                    ->pluck('cliente_matricula_id')
                    ->filter()
                    ->unique()
                    ->values();

                foreach ($matriculaIds as $matriculaId) {
                    $matricula = ClienteMatricula::query()->find($matriculaId);
                    if (! $matricula) {
                        continue;
                    }

                    $matricula->update([
                        'precio_lista' => (float) $debt->monto_total,
                        'precio_final' => (float) $debt->monto_total,
                        'modalidad_pago' => 'cuotas',
                        'requiere_plan_cuotas' => true,
                        'cuota_inicial_monto' => 0,
                    ]);
                }
            });

            $summary['reparadas']++;
            $summary['reparadas_por_pago_aplicado_a_cuotas']++;
        }

        $this->info(sprintf(
            'Modo: %s | Evaluadas: %d | Reparables: %d | Reparadas: %d | Inicial separada: %d | Pago aplicado a cuotas: %d | Omitidas pagos reales: %d | Omitidas sin cuotas: %d | Omitidas pago no alineado: %d',
            $execute ? 'execute' : 'dry-run',
            $summary['evaluadas'],
            $summary['reparables'],
            $summary['reparadas'],
            $summary['reparadas_por_inicial_separada'],
            $summary['reparadas_por_pago_aplicado_a_cuotas'],
            $summary['omitidas_con_pagos_reales'],
            $summary['omitidas_sin_cuotas'],
            $summary['omitidas_pago_no_alineado']
        ));

        return self::SUCCESS;
    }
}
