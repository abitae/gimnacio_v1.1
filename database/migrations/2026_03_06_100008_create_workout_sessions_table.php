<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_routine_id')->constrained('client_routines')->onDelete('cascade');
            $table->foreignId('client_routine_day_id')->nullable()->constrained('client_routine_days')->onDelete('set null');
            $table->dateTime('fecha_hora');
            $table->string('estado')->default('iniciada'); // iniciada, completada
            $table->text('notas')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_routine_id');
            $table->index('fecha_hora');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_sessions');
    }
};
