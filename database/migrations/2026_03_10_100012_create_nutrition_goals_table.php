<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nutrition_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('trainer_user_id')->constrained('users')->onDelete('restrict');
            $table->string('objetivo', 60)->comment('bajar_grasa, ganar_masa, mejorar_resistencia, mejorar_salud, mantener_peso, personalizado');
            $table->string('objetivo_personalizado', 200)->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_objetivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado', 20)->default('activo')->comment('activo, cumplido, cancelado');
            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id');
            $table->index('trainer_user_id');
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nutrition_goals');
    }
};
