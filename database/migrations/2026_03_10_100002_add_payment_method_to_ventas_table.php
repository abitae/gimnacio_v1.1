<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->foreignId('payment_method_id')->nullable()->after('metodo_pago')->constrained('payment_methods')->onDelete('set null');
            $table->string('numero_operacion', 60)->nullable()->after('payment_method_id');
            $table->string('entidad_financiera', 100)->nullable()->after('numero_operacion');
            $table->index('payment_method_id');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['payment_method_id']);
            $table->dropIndex(['payment_method_id']);
            $table->dropColumn(['payment_method_id', 'numero_operacion', 'entidad_financiera']);
        });
    }
};
