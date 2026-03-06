<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('evaluaciones_medidas_nutricion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('estatura', 5, 2)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->decimal('porcentaje_grasa', 5, 2)->nullable();
            $table->decimal('porcentaje_musculo', 5, 2)->nullable();
            $table->decimal('masa_muscular', 5, 2)->nullable();
            $table->decimal('masa_grasa', 5, 2)->nullable();
            $table->decimal('masa_osea', 5, 2)->nullable();
            $table->decimal('masa_residual', 5, 2)->nullable();
            $table->json('circunferencias')->nullable();
            $table->string('presion_arterial', 20)->nullable();
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->string('objetivo')->nullable();
            $table->foreignId('nutricionista_id')->nullable()->constrained('users')->onDelete('set null');
            $table->date('fecha_proxima_evaluacion')->nullable();
            $table->enum('estado', ['pendiente', 'completada', 'cancelada'])->default('completada');
            $table->text('observaciones')->nullable();
            $table->foreignId('evaluado_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('evaluado_por');
            $table->index('nutricionista_id');
            $table->index('fecha_proxima_evaluacion');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_medidas_nutricion');
    }
};
