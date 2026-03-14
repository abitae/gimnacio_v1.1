<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cliente_plan_traspasos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->cascadeOnDelete();
            $table->string('origen_tipo', 100);
            $table->unsignedBigInteger('origen_id');
            $table->string('plan_anterior_tipo', 30)->nullable();
            $table->unsignedBigInteger('plan_anterior_id')->nullable();
            $table->string('plan_nuevo_tipo', 30)->nullable();
            $table->unsignedBigInteger('plan_nuevo_id')->nullable();
            $table->text('motivo')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['cliente_id', 'created_at']);
            $table->index(['origen_tipo', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cliente_plan_traspasos');
    }
};
