<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->foreignId('lead_origen_id')
                ->nullable()
                ->after('origen')
                ->constrained('crm_leads')
                ->nullOnDelete();
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->foreignId('converted_by')
                ->nullable()
                ->after('cliente_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('converted_at')->nullable()->after('converted_by');
        });

        Schema::table('crm_campaigns', function (Blueprint $table) {
            $table->foreignId('discount_coupon_id')
                ->nullable()
                ->after('filtros')
                ->constrained('discount_coupons')
                ->nullOnDelete();
        });

        Schema::table('crm_mensajes', function (Blueprint $table) {
            $table->foreignId('lead_id')
                ->nullable()
                ->after('cliente_id')
                ->constrained('crm_leads')
                ->nullOnDelete();
            $table->index('lead_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('crm_mensajes', function (Blueprint $table) {
                $table->dropForeign(['cliente_id']);
            });
            DB::statement('ALTER TABLE crm_mensajes MODIFY cliente_id BIGINT UNSIGNED NULL');
            Schema::table('crm_mensajes', function (Blueprint $table) {
                $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('crm_mensajes', function (Blueprint $table) {
                $table->dropForeign(['cliente_id']);
            });
            DB::statement('ALTER TABLE crm_mensajes MODIFY cliente_id BIGINT UNSIGNED NOT NULL');
            Schema::table('crm_mensajes', function (Blueprint $table) {
                $table->foreign('cliente_id')->references('id')->on('clientes')->restrictOnDelete();
            });
        }

        Schema::table('crm_mensajes', function (Blueprint $table) {
            $table->dropForeign(['lead_id']);
            $table->dropColumn('lead_id');
        });

        Schema::table('crm_campaigns', function (Blueprint $table) {
            $table->dropForeign(['discount_coupon_id']);
            $table->dropColumn('discount_coupon_id');
        });

        Schema::table('crm_leads', function (Blueprint $table) {
            $table->dropForeign(['converted_by']);
            $table->dropColumn(['converted_by', 'converted_at']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropForeign(['lead_origen_id']);
            $table->dropColumn('lead_origen_id');
        });
    }
};
