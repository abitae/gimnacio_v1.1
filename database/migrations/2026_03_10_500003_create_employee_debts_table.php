<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('venta_id')->constrained('ventas')->onDelete('cascade');
            $table->decimal('monto_total', 12, 2);
            $table->decimal('monto_abonado', 12, 2)->default(0);
            $table->decimal('saldo_pendiente', 12, 2);
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 20)->default('pendiente'); // pendiente, parcial, pagado, vencido
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('employee_id');
            $table->index('venta_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_debts');
    }
};
