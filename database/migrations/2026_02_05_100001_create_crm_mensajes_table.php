<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crm_mensajes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('clientes')->onDelete('restrict');
            $table->enum('canal', ['whatsapp', 'email', 'sms'])->default('whatsapp');
            $table->string('destino', 100);
            $table->text('contenido');
            $table->enum('estado', ['pendiente', 'enviado', 'fallido', 'entregado'])->default('pendiente');
            $table->timestamp('enviado_at')->nullable();
            $table->text('error_mensaje')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('cliente_id');
            $table->index('canal');
            $table->index('estado');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crm_mensajes');
    }
};
