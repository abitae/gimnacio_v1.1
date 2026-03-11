<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rentable_space_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rentable_space_id')->constrained('rentable_spaces')->onDelete('cascade');
            $table->string('tipo_tarifa', 30)->default('por_hora'); // por_hora, rango_horario, dia_especial
            $table->string('nombre', 100)->nullable();
            $table->decimal('precio', 12, 2);
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->unsignedTinyInteger('dia_semana')->nullable(); // 0-6
            $table->date('fecha_especial')->nullable();
            $table->unsignedBigInteger('sede_id')->nullable();
            $table->timestamps();

            $table->index('rentable_space_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rentable_space_rates');
    }
};
