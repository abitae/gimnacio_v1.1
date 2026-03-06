<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_routines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->foreignId('routine_template_id')->nullable()->constrained('routine_templates')->onDelete('set null');
            $table->foreignId('trainer_user_id')->constrained('users')->onDelete('restrict');
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('objetivo_personal')->nullable();
            $table->text('restricciones')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estado')->default('activa'); // activa, pausada, finalizada
            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_inicio');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_routines');
    }
};
