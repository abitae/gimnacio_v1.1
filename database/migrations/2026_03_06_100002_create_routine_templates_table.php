<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_templates', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('objetivo')->nullable();
            $table->string('nivel')->nullable();
            $table->unsignedInteger('duracion_semanas')->nullable();
            $table->unsignedInteger('frecuencia_dias_semana')->nullable();
            $table->text('descripcion')->nullable();
            $table->json('tags')->nullable();
            $table->string('estado')->default('borrador'); // borrador, activa
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_templates');
    }
};
