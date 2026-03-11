<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_installment_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_matricula_id')->constrained('cliente_matriculas')->onDelete('cascade');
            $table->decimal('monto_total', 12, 2);
            $table->unsignedSmallInteger('numero_cuotas');
            $table->decimal('monto_cuota', 12, 2);
            $table->string('frecuencia', 20)->default('mensual'); // semanal, quincenal, mensual, personalizado
            $table->date('fecha_inicio');
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index('cliente_matricula_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_installment_plans');
    }
};
