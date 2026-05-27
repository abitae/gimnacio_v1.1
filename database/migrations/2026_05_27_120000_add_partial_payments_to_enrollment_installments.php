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
            $table->decimal('monto_pagado', 12, 2)->default(0)->after('monto');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('enrollment_installment_id')
                ->nullable()
                ->after('cliente_matricula_id')
                ->constrained('enrollment_installments')
                ->nullOnDelete();
            $table->index('enrollment_installment_id');
        });

        DB::table('enrollment_installments')
            ->where('estado', 'pagada')
            ->update(['monto_pagado' => DB::raw('monto')]);

        $paidInstallments = DB::table('enrollment_installments')
            ->whereNotNull('pago_id')
            ->pluck('id', 'pago_id');

        foreach ($paidInstallments as $pagoId => $installmentId) {
            DB::table('pagos')
                ->where('id', $pagoId)
                ->update(['enrollment_installment_id' => $installmentId]);
        }

        $partialInstallments = DB::table('enrollment_installments')
            ->join('pagos', 'pagos.id', '=', 'enrollment_installments.pago_id')
            ->where('enrollment_installments.estado', 'parcial')
            ->select([
                'enrollment_installments.id',
                'enrollment_installments.monto',
                'pagos.monto as pago_monto',
            ])
            ->get();

        foreach ($partialInstallments as $installment) {
            DB::table('enrollment_installments')
                ->where('id', $installment->id)
                ->update([
                    'monto_pagado' => min((float) $installment->monto, (float) $installment->pago_monto),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['enrollment_installment_id']);
            $table->dropIndex(['enrollment_installment_id']);
            $table->dropColumn('enrollment_installment_id');
        });

        Schema::table('enrollment_installments', function (Blueprint $table) {
            $table->dropColumn('monto_pagado');
        });
    }
};
