<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_session_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_session_exercise_id')->constrained('workout_session_exercises')->onDelete('cascade');
            $table->unsignedInteger('set_numero');
            $table->decimal('peso', 8, 2)->nullable();
            $table->unsignedInteger('repeticiones')->nullable();
            $table->decimal('rpe', 4, 1)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('workout_session_exercise_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_session_sets');
    }
};
