<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_goal_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nutrition_goal_id')->constrained('nutrition_goals')->onDelete('cascade');
            $table->date('fecha');
            $table->decimal('peso', 5, 2)->nullable();
            $table->json('medidas')->nullable();
            $table->text('observaciones')->nullable();
            $table->json('fotos')->nullable()->comment('rutas de archivos');
            $table->string('adherencia', 40)->nullable();
            $table->text('progreso_general')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->onDelete('restrict');
            $table->timestamps();

            $table->index('nutrition_goal_id');
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_goal_progress');
    }
};
