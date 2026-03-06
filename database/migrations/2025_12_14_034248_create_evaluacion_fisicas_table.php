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
        Schema::create('evaluacion_fisicas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->decimal('peso', 5, 2)->nullable();
            $table->decimal('estatura', 5, 2)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->decimal('porcentaje_grasa', 5, 2)->nullable();
            $table->decimal('porcentaje_musculo', 5, 2)->nullable();
            $table->json('perimetros_corporales')->nullable();
            $table->string('presion_arterial', 20)->nullable();
            $table->integer('frecuencia_cardiaca')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('evaluado_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('evaluado_por');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluacion_fisicas');
    }
};
