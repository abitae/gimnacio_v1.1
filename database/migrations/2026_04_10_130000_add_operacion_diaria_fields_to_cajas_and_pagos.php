<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cajas', function (Blueprint $table) {
            $table->decimal('saldo_contado_cierre', 10, 2)->nullable()->after('saldo_final');
            $table->decimal('diferencia_cierre', 10, 2)->nullable()->after('saldo_contado_cierre');
        });

        Schema::table('pagos', function (Blueprint $table) {
            $table->foreignId('client_debt_id')
                ->nullable()
                ->after('cliente_matricula_id')
                ->constrained('client_debts')
                ->nullOnDelete();

            $table->index('client_debt_id');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['client_debt_id']);
            $table->dropIndex(['client_debt_id']);
            $table->dropColumn('client_debt_id');
        });

        Schema::table('cajas', function (Blueprint $table) {
            $table->dropColumn(['saldo_contado_cierre', 'diferencia_cierre']);
        });
    }
};
