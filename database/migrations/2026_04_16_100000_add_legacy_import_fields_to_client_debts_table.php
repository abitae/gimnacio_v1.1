<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_debts', function (Blueprint $table) {
            $table->string('tipo_deuda', 30)->default('general')->after('origen_id');
            $table->string('referencia', 255)->nullable()->after('tipo_deuda');
            $table->date('plan_fecha_inicio')->nullable()->after('referencia');
            $table->date('plan_fecha_fin')->nullable()->after('plan_fecha_inicio');

            $table->index(['cliente_id', 'sucursal_id', 'tipo_deuda'], 'client_debts_cliente_sucursal_tipo_idx');
            $table->index(
                ['cliente_id', 'sucursal_id', 'tipo_deuda', 'referencia', 'plan_fecha_inicio', 'plan_fecha_fin'],
                'client_debts_cuotificada_lookup_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('client_debts', function (Blueprint $table) {
            $table->dropIndex('client_debts_cliente_sucursal_tipo_idx');
            $table->dropIndex('client_debts_cuotificada_lookup_idx');
            $table->dropColumn(['tipo_deuda', 'referencia', 'plan_fecha_inicio', 'plan_fecha_fin']);
        });
    }
};
