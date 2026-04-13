<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accent', 20)->nullable()->default('red')->change();
            $table->string('sidebar_bg', 20)->nullable()->default('red')->change();
            $table->string('header_bg', 20)->nullable()->default('red')->change();
            $table->string('appearance_sidebar', 20)->nullable()->default('dark')->change();
            $table->string('appearance_header', 20)->nullable()->default('dark')->change();
        });

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('accent')->orWhere('accent', 'neutral');
            })
            ->update(['accent' => 'red']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('sidebar_bg')->orWhere('sidebar_bg', 'default');
            })
            ->update(['sidebar_bg' => 'red']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('header_bg')->orWhere('header_bg', 'default');
            })
            ->update(['header_bg' => 'red']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('appearance_sidebar')->orWhere('appearance_sidebar', 'system');
            })
            ->update(['appearance_sidebar' => 'dark']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('appearance_header')->orWhere('appearance_header', 'system');
            })
            ->update(['appearance_header' => 'dark']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('accent', 20)->nullable()->default('neutral')->change();
            $table->string('sidebar_bg', 20)->nullable()->default('default')->change();
            $table->string('header_bg', 20)->nullable()->default('default')->change();
            $table->string('appearance_sidebar', 20)->nullable()->default('system')->change();
            $table->string('appearance_header', 20)->nullable()->default('system')->change();
        });

        DB::table('users')
            ->where('accent', 'red')
            ->update(['accent' => 'neutral']);

        DB::table('users')
            ->where('sidebar_bg', 'red')
            ->update(['sidebar_bg' => 'default']);

        DB::table('users')
            ->where('header_bg', 'red')
            ->update(['header_bg' => 'default']);

        DB::table('users')
            ->where('appearance_sidebar', 'dark')
            ->update(['appearance_sidebar' => 'system']);

        DB::table('users')
            ->where('appearance_header', 'dark')
            ->update(['appearance_header' => 'system']);
    }
};