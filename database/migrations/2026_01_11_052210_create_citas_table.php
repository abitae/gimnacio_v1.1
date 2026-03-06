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
        Schema::create('citas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->enum('tipo', ['evaluacion', 'consulta_nutricional', 'seguimiento', 'otro'])->default('evaluacion');
            $table->dateTime('fecha_hora');
            $table->integer('duracion_minutos')->default(60);
            $table->foreignId('nutricionista_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('trainer_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('estado', ['programada', 'confirmada', 'en_curso', 'completada', 'cancelada', 'no_asistio'])->default('programada');
            $table->text('observaciones')->nullable();
            $table->foreignId('evaluacion_medidas_nutricion_id')->nullable()->constrained('evaluaciones_medidas_nutricion')->onDelete('set null');
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('fecha_hora');
            $table->index('estado');
            $table->index('nutricionista_id');
            $table->index('trainer_user_id');
            $table->index('evaluacion_medidas_nutricion_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};
