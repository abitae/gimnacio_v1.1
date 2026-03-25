<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_installments', function (Blueprint $table) {
            $table->foreignId('cliente_matricula_id')->nullable()->after('enrollment_installment_plan_id')->constrained('cliente_matriculas')->nullOnDelete();
            $table->index('cliente_matricula_id');
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->foreignId('cliente_id')->nullable()->after('id')->constrained('clientes')->cascadeOnDelete();
            $table->index('cliente_id');
        });

        $this->backfillAndMergePlans();

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->dropForeign(['cliente_matricula_id']);
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('cliente_matricula_id')->nullable()->change();
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->foreign('cliente_matricula_id')->references('id')->on('cliente_matriculas')->nullOnDelete();
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->unique('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->dropUnique(['cliente_id']);
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->dropForeign(['cliente_id']);
            $table->dropColumn('cliente_id');
        });

        Schema::table('enrollment_installments', function (Blueprint $table) {
            $table->dropForeign(['cliente_matricula_id']);
            $table->dropColumn('cliente_matricula_id');
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->dropForeign(['cliente_matricula_id']);
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            // Revert to NOT NULL only if no nulls exist — best-effort for rollback
            DB::table('enrollment_installment_plans')->whereNull('cliente_matricula_id')->delete();

            $table->unsignedBigInteger('cliente_matricula_id')->nullable(false)->change();
        });

        Schema::table('enrollment_installment_plans', function (Blueprint $table) {
            $table->foreign('cliente_matricula_id')->references('id')->on('cliente_matriculas')->cascadeOnDelete();
        });
    }

    private function backfillAndMergePlans(): void
    {
        $plans = DB::table('enrollment_installment_plans')->orderBy('id')->get();

        foreach ($plans as $plan) {
            $clienteId = DB::table('cliente_matriculas')
                ->where('id', $plan->cliente_matricula_id)
                ->value('cliente_id');

            if ($clienteId) {
                DB::table('enrollment_installment_plans')->where('id', $plan->id)->update(['cliente_id' => $clienteId]);
            }

            DB::table('enrollment_installments')
                ->where('enrollment_installment_plan_id', $plan->id)
                ->update(['cliente_matricula_id' => $plan->cliente_matricula_id]);
        }

        $clienteIds = DB::table('enrollment_installment_plans')
            ->whereNotNull('cliente_id')
            ->distinct()
            ->pluck('cliente_id');

        foreach ($clienteIds as $clienteId) {
            $planIds = DB::table('enrollment_installment_plans')
                ->where('cliente_id', $clienteId)
                ->orderBy('id')
                ->pluck('id');

            if ($planIds->count() <= 1) {
                continue;
            }

            $canonicalId = $planIds->last();
            $others = $planIds->slice(0, -1)->values();

            foreach ($others as $oldPlanId) {
                DB::table('enrollment_installments')
                    ->where('enrollment_installment_plan_id', $oldPlanId)
                    ->update(['enrollment_installment_plan_id' => $canonicalId]);
                DB::table('enrollment_installment_plans')->where('id', $oldPlanId)->delete();
            }

            $this->renumberAndSyncPlanHeader((int) $canonicalId);
        }

        foreach (DB::table('enrollment_installment_plans')->whereNull('cliente_id')->pluck('id') as $orphanId) {
            DB::table('enrollment_installments')->where('enrollment_installment_plan_id', $orphanId)->delete();
            DB::table('enrollment_installment_plans')->where('id', $orphanId)->delete();
        }

        foreach (DB::table('enrollment_installment_plans')->pluck('id') as $planId) {
            $this->renumberAndSyncPlanHeader((int) $planId);
        }
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

        DB::table('enrollment_installment_plans')->where('id', $planId)->update([
            'monto_total' => round($sum, 2),
            'numero_cuotas' => $count,
            'monto_cuota' => $count > 0 ? round($sum / $count, 2) : 0,
            'fecha_inicio' => $minFecha ?? DB::table('enrollment_installment_plans')->where('id', $planId)->value('fecha_inicio'),
        ]);
    }
};
