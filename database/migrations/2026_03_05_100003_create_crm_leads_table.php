<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_leads', function (Blueprint $table) {
            $table->id();
            $table->enum('tipo_documento', ['DNI', 'CE'])->nullable();
            $table->string('numero_documento', 20)->nullable();
            $table->string('nombres', 100)->nullable();
            $table->string('apellidos', 100)->nullable();
            $table->string('telefono', 20);
            $table->string('whatsapp', 20)->nullable();
            $table->string('email')->nullable();
            $table->string('canal_origen', 60)->nullable();
            $table->string('sede', 80)->nullable();
            $table->string('interes_principal', 120)->nullable();
            $table->string('estado', 40)->default('nuevo');
            $table->foreignId('stage_id')->constrained('crm_stages')->onDelete('restrict');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('set null');
            $table->dateTime('fecha_ultimo_contacto')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tipo_documento', 'numero_documento']);
            $table->index('telefono');
            $table->index('stage_id');
            $table->index('estado');
            $table->index('assigned_to');
            $table->index('cliente_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_leads');
    }
};
