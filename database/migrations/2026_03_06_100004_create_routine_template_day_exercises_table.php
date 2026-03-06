<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('routine_template_day_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('routine_template_day_id')->constrained('routine_template_days')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('restrict');
            $table->unsignedInteger('series')->default(3);
            $table->string('repeticiones')->nullable(); // e.g. "8-12"
            $table->unsignedInteger('descanso_segundos')->nullable();
            $table->string('tempo')->nullable();
            $table->string('intensidad_rpe')->nullable();
            $table->string('metodo')->default('normal'); // normal, superserie, circuito, drop_set
            $table->text('notas')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index(['routine_template_day_id', 'orden'], 'rtde_day_orden');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('routine_template_day_exercises');
    }
};
