<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_session_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_id')->constrained('workout_sessions')->onDelete('cascade');
            $table->foreignId('exercise_id')->constrained('exercises')->onDelete('restrict');
            $table->foreignId('client_routine_day_exercise_id')->nullable()->constrained('client_routine_day_exercises')->onDelete('set null');
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();

            $table->index('workout_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_session_exercises');
    }
};
