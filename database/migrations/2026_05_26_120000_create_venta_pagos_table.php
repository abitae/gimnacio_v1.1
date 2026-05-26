<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->decimal('monto', 12, 2);
            $table->string('metodo_pago', 80)->nullable();
            $table->string('numero_operacion', 60)->nullable();
            $table->string('entidad_financiera', 120)->nullable();
            $table->dateTime('pagado_en');
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->timestamps();

            $table->index(['venta_id', 'pagado_en']);
            $table->index(['payment_method_id', 'pagado_en']);
            $table->index(['caja_id', 'pagado_en']);
            $table->index(['sucursal_id', 'pagado_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venta_pagos');
    }
};
