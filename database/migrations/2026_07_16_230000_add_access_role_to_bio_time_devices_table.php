<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->string('access_role', 16)->nullable()->after('is_attendance');
        });
    }

    public function down(): void
    {
        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->dropColumn('access_role');
        });
    }
};
