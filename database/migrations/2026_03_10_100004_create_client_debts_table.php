<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->onDelete('set null');
            $table->string('origen_tipo', 60)->comment('Pos, Matricula, Membresia, Alquiler, etc.');
            $table->unsignedBigInteger('origen_id')->nullable();
            $table->decimal('monto_total', 10, 2);
            $table->decimal('monto_pagado', 10, 2)->default(0);
            $table->decimal('saldo_pendiente', 10, 2);
            $table->date('fecha_registro');
            $table->date('fecha_vencimiento')->nullable();
            $table->string('estado', 20)->default('pendiente')->comment('pendiente, parcial, pagado, vencido');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('cliente_id');
            $table->index('estado');
            $table->index('fecha_vencimiento');
            $table->index(['origen_tipo', 'origen_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_debts');
    }
};
