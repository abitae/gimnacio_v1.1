<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_time_access_commands', function (Blueprint $table) {
            $table->boolean('ensure_create')->default(false)->after('desired_area_biotime_id');
            $table->string('first_name', 100)->nullable()->after('ensure_create');
            $table->string('last_name', 100)->nullable()->after('first_name');
        });

        Schema::table('bio_time_sucursal_settings', function (Blueprint $table) {
            $table->unsignedInteger('employee_limit')->default(500)->after('enabled');
            $table->unsignedInteger('employees_count')->nullable()->after('employee_limit');
        });
    }

    public function down(): void
    {
        Schema::table('bio_time_access_commands', function (Blueprint $table) {
            $table->dropColumn(['ensure_create', 'first_name', 'last_name']);
        });

        Schema::table('bio_time_sucursal_settings', function (Blueprint $table) {
            $table->dropColumn(['employee_limit', 'employees_count']);
        });
    }
};
