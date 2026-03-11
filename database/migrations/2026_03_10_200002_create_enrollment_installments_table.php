<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_installment_plan_id')->constrained('enrollment_installment_plans')->onDelete('cascade');
            $table->unsignedSmallInteger('numero_cuota');
            $table->decimal('monto', 12, 2);
            $table->date('fecha_vencimiento');
            $table->string('estado', 20)->default('pendiente'); // pendiente, pagada, vencida, parcial
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->string('numero_operacion', 60)->nullable();
            $table->foreignId('pago_id')->nullable()->constrained('pagos')->onDelete('set null');
            $table->date('fecha_pago')->nullable();
            $table->timestamps();

            $table->index('enrollment_installment_plan_id');
            $table->index(['estado', 'fecha_vencimiento']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_installments');
    }
};
