<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('appearance_sidebar', 20)->nullable()->default('system')->after('header_bg');
            $table->string('appearance_header', 20)->nullable()->default('system')->after('appearance_sidebar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['appearance_sidebar', 'appearance_header']);
        });
    }
};
