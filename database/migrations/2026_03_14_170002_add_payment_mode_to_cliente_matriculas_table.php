<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cliente_matriculas', function (Blueprint $table) {
            $table->string('modalidad_pago', 20)->default('contado')->after('precio_final');
            $table->boolean('requiere_plan_cuotas')->default(false)->after('modalidad_pago');
            $table->decimal('cuota_inicial_monto', 12, 2)->default(0)->after('requiere_plan_cuotas');
        });
    }

    public function down(): void
    {
        Schema::table('cliente_matriculas', function (Blueprint $table) {
            $table->dropColumn([
                'modalidad_pago',
                'requiere_plan_cuotas',
                'cuota_inicial_monto',
            ]);
        });
    }
};
