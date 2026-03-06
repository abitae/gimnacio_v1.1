<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('grupo_muscular_principal')->nullable();
            $table->json('musculos_secundarios')->nullable();
            $table->string('tipo'); // fuerza, hipertrofia, cardio, movilidad, estiramiento
            $table->string('nivel')->nullable();
            $table->string('equipamiento')->nullable();
            $table->text('descripcion_tecnica')->nullable();
            $table->text('errores_comunes')->nullable();
            $table->text('consejos_seguridad')->nullable();
            $table->string('video_url')->nullable();
            $table->string('estado')->default('activo'); // activo, inactivo
            $table->timestamps();
            $table->softDeletes();

            $table->index('estado');
            $table->index('tipo');
            $table->index('grupo_muscular_principal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
