<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->boolean('es_credito')->default(false)->after('observaciones');
            $table->decimal('monto_inicial', 10, 2)->nullable()->after('es_credito');
            $table->date('fecha_vencimiento_deuda')->nullable()->after('monto_inicial');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['es_credito', 'monto_inicial', 'fecha_vencimiento_deuda']);
        });
    }
};
