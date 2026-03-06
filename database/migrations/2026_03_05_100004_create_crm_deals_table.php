<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('crm_leads')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->foreignId('membresia_id')->nullable()->constrained('membresias')->onDelete('set null');
            $table->decimal('precio_objetivo', 12, 2)->default(0);
            $table->decimal('descuento_sugerido', 12, 2)->default(0);
            $table->unsignedTinyInteger('probabilidad')->default(0);
            $table->date('fecha_estimada_cierre')->nullable();
            $table->string('estado', 20)->default('open');
            $table->text('motivo_interes')->nullable();
            $table->text('objeciones')->nullable();
            $table->foreignId('motivo_perdida_id')->nullable()->constrained('loss_reasons')->onDelete('set null');
            $table->text('notas')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index('lead_id');
            $table->index('cliente_id');
            $table->index('estado');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_deals');
    }
};
