<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_routine_day_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_routine_day_id')->constrained('client_routine_days')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('restrict');
            $table->unsignedInteger('series')->default(3);
            $table->string('repeticiones')->nullable();
            $table->unsignedInteger('descanso_segundos')->nullable();
            $table->string('tempo')->nullable();
            $table->string('intensidad_rpe')->nullable();
            $table->string('metodo')->default('normal');
            $table->text('notas')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index('client_routine_day_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_routine_day_exercises');
    }
};
