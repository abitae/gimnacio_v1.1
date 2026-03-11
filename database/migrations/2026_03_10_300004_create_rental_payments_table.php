<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_id')->constrained('rentals')->onDelete('cascade');
            $table->decimal('monto', 12, 2);
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->string('numero_operacion', 60)->nullable();
            $table->string('entidad_financiera', 80)->nullable();
            $table->date('fecha_pago');
            $table->foreignId('caja_id')->nullable()->constrained('cajas')->onDelete('set null');
            $table->timestamps();

            $table->index('rental_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_payments');
    }
};
