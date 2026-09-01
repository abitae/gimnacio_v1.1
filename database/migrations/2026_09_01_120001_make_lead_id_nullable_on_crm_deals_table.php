<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_deals', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
        });

        Schema::table('crm_deals', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable()->change();
        });

        Schema::table('crm_deals', function (Blueprint $table) {
            $table->foreign('lead_id')->references('id')->on('crm_leads')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_deals', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
        });

        Schema::table('crm_deals', function (Blueprint $table) {
            $table->foreignId('lead_id')->nullable(false)->change();
        });

        Schema::table('crm_deals', function (Blueprint $table) {
            $table->foreign('lead_id')->references('id')->on('crm_leads')->cascadeOnDelete();
        });
    }
};
