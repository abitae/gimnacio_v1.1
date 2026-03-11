<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('cascade');
            $table->text('enfermedades')->nullable();
            $table->text('alergias')->nullable();
            $table->text('medicacion')->nullable();
            $table->text('restricciones_medicas')->nullable();
            $table->text('lesiones')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->unique('cliente_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('health_records');
    }
};
