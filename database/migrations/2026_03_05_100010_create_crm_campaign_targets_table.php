<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_campaign_targets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('crm_campaigns')->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained('crm_leads')->onDelete('cascade');
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('estado', 30)->default('pending');
            $table->dateTime('last_activity_at')->nullable();
            $table->timestamps();

            $table->index('campaign_id');
            $table->index(['lead_id', 'cliente_id']);
            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_campaign_targets');
    }
};
