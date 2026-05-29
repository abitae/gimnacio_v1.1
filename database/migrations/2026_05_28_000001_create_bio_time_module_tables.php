<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bio_time_settings', function (Blueprint $table) {
            $table->id();
            $table->text('webhook_secret')->nullable();
            $table->timestamp('last_received_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biotime_id')->unique();
            $table->string('dept_code', 50)->nullable()->index();
            $table->string('dept_name')->nullable();
            $table->unsignedBigInteger('parent_biotime_id')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biotime_id')->unique();
            $table->string('area_code', 50)->nullable()->index();
            $table->string('area_name')->nullable();
            $table->unsignedBigInteger('parent_biotime_id')->nullable()->index();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biotime_id')->nullable()->unique();
            $table->string('serial_number')->nullable()->unique();
            $table->string('alias')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('state')->nullable()->index();
            $table->unsignedBigInteger('area_biotime_id')->nullable()->index();
            $table->timestamp('last_activity')->nullable()->index();
            $table->boolean('is_attendance')->default(false);
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biotime_id')->nullable()->unique();
            $table->string('emp_code', 50)->nullable()->index();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('department_biotime_id')->nullable()->index();
            $table->string('department_name')->nullable();
            $table->integer('app_status')->nullable();
            $table->string('mobile')->nullable();
            $table->string('email')->nullable();
            $table->date('hire_date')->nullable();
            $table->string('card_no')->nullable();
            $table->json('area_biotime_ids')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('biotime_id')->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->foreignId('asistencia_id')->nullable()->constrained('asistencias')->nullOnDelete();
            $table->string('emp_code', 50)->nullable()->index();
            $table->dateTime('punch_time')->nullable()->index();
            $table->string('punch_state', 20)->nullable();
            $table->string('punch_state_display')->nullable();
            $table->integer('verify_type')->nullable();
            $table->string('terminal_sn')->nullable()->index();
            $table->string('terminal_alias')->nullable();
            $table->dateTime('upload_time')->nullable();
            $table->string('department_name')->nullable();
            $table->string('position_name')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_sync_batches', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->unique();
            $table->string('entity', 40)->index();
            $table->string('status', 20)->default('pending')->index();
            $table->unsignedInteger('received')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->timestamp('agent_timestamp')->nullable();
            $table->timestamp('received_at')->nullable()->index();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('bio_time_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->uuid('batch_id')->index();
            $table->string('entity', 40)->index();
            $table->unsignedBigInteger('biotime_id')->nullable()->index();
            $table->string('status', 20)->index();
            $table->string('action', 40)->nullable();
            $table->json('payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('bio_time_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('mapping_type', 30)->index();
            $table->unsignedBigInteger('biotime_id')->index();
            $table->string('target_type', 30)->index();
            $table->unsignedBigInteger('target_id')->index();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->timestamps();

            $table->unique(['mapping_type', 'biotime_id'], 'bio_time_mappings_source_unique');
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->unsignedBigInteger('biotime_id')->nullable()->after('codigo');
            $table->unique(['sucursal_id', 'biotime_id'], 'clientes_sucursal_biotime_unique');
        });

        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE asistencias MODIFY origen ENUM('manual','app','biotime') NOT NULL DEFAULT 'manual'");
            if (Schema::hasColumn('asistencias', 'checkout_origen')) {
                DB::statement("ALTER TABLE asistencias MODIFY checkout_origen ENUM('manual','automatico','biotime') NULL");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('clientes', 'biotime_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropUnique('clientes_sucursal_biotime_unique');
                $table->dropColumn('biotime_id');
            });
        }

        Schema::dropIfExists('bio_time_mappings');
        Schema::dropIfExists('bio_time_sync_logs');
        Schema::dropIfExists('bio_time_sync_batches');
        Schema::dropIfExists('bio_time_transactions');
        Schema::dropIfExists('bio_time_employees');
        Schema::dropIfExists('bio_time_devices');
        Schema::dropIfExists('bio_time_areas');
        Schema::dropIfExists('bio_time_departments');
        Schema::dropIfExists('bio_time_settings');
    }
};
