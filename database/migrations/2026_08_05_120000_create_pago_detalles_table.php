<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pago_id')->constrained('pagos')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago', 80);
            $table->string('numero_operacion', 60)->nullable();
            $table->string('entidad_financiera', 120)->nullable();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['pago_id', 'payment_method_id']);
            $table->index(['caja_id', 'created_at']);
            $table->index(['sucursal_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_detalles');
    }
};
