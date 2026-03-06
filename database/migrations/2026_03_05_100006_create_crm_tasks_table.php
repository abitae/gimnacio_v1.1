<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('cascade');
            $table->foreignId('deal_id')->nullable()->constrained('crm_deals')->onDelete('set null');
            $table->string('tipo', 40);
            $table->dateTime('fecha_hora_programada');
            $table->string('prioridad', 20)->default('medium');
            $table->string('estado', 20)->default('pending');
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('assigned_to')->constrained('users')->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notas')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('lead_id');
            $table->index('cliente_id');
            $table->index('deal_id');
            $table->index('assigned_to');
            $table->index('estado');
            $table->index('fecha_hora_programada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_tasks');
    }
};
