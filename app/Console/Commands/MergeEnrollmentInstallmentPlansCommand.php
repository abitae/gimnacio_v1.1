<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Idempotente: fusiona planes duplicados por cliente_id (reparación post-importación).
 */
class MergeEnrollmentInstallmentPlansCommand extends Command
{
    protected $signature = 'enrollment:merge-installment-plans {--dry-run : Solo mostrar acciones}';

    protected $description = 'Fusiona planes de cuotas duplicados por cliente (un plan por cliente)';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $clienteIds = DB::table('enrollment_installment_plans')
            ->whereNotNull('cliente_id')
            ->select('cliente_id')
            ->groupBy('cliente_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('cliente_id');

        if ($clienteIds->isEmpty()) {
            $this->info('No hay clientes con planes duplicados.');

            return self::SUCCESS;
        }

        foreach ($clienteIds as $clienteId) {
            $planIds = DB::table('enrollment_installment_plans')
                ->where('cliente_id', $clienteId)
                ->orderBy('id')
                ->pluck('id');

            $canonicalId = $planIds->last();
            $others = $planIds->slice(0, -1)->values();

            $this->line("Cliente {$clienteId}: conservando plan {$canonicalId}, fusionando ".$others->implode(', '));

            if ($dry) {
                continue;
            }

            foreach ($others as $oldPlanId) {
                DB::table('enrollment_installments')
                    ->where('enrollment_installment_plan_id', $oldPlanId)
                    ->update(['enrollment_installment_plan_id' => $canonicalId]);
                DB::table('enrollment_installment_plans')->where('id', $oldPlanId)->delete();
            }

            $this->renumberAndSyncPlanHeader((int) $canonicalId);
        }

        $this->info($dry ? 'Dry-run finalizado.' : 'Fusión completada.');

        return self::SUCCESS;
    }

    private function renumberAndSyncPlanHeader(int $planId): void
    {
        $rows = DB::table('enrollment_installments')
            ->where('enrollment_installment_plan_id', $planId)
            ->orderBy('fecha_vencimiento')
            ->orderBy('id')
            ->get();

        foreach ($rows as $index => $row) {
            DB::table('enrollment_installments')
                ->where('id', $row->id)
                ->update(['numero_cuota' => $index + 1]);
        }

        $sum = (float) DB::table('enrollment_installments')
            ->where('enrollment_installment_plan_id', $planId)
            ->sum('monto');

        $count = (int) DB::table('enrollment_installments')
            ->where('enrollment_installment_plan_id', $planId)
            ->count();

        $minFecha = DB::table('enrollment_installments')
            ->where('enrollment_installment_plan_id', $planId)
            ->min('fecha_vencimiento');

        $currentInicio = DB::table('enrollment_installment_plans')->where('id', $planId)->value('fecha_inicio');

        DB::table('enrollment_installment_plans')->where('id', $planId)->update([
            'monto_total' => round($sum, 2),
            'numero_cuotas' => $count,
            'monto_cuota' => $count > 0 ? round($sum / $count, 2) : 0,
            'fecha_inicio' => $minFecha ?? $currentInicio,
        ]);
    }
}
