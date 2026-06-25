<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('appearance')
                    ->orWhereIn('appearance', ['dark', 'system']);
            })
            ->update(['appearance' => 'light']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('appearance_sidebar')
                    ->orWhereIn('appearance_sidebar', ['dark', 'system']);
            })
            ->update(['appearance_sidebar' => 'light']);

        DB::table('users')
            ->where(function ($query) {
                $query->whereNull('appearance_header')
                    ->orWhereIn('appearance_header', ['dark', 'system']);
            })
            ->update(['appearance_header' => 'light']);

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('appearance', 20)->nullable()->default('light')->change();
                $table->string('appearance_sidebar', 20)->nullable()->default('light')->change();
                $table->string('appearance_header', 20)->nullable()->default('light')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('users', function (Blueprint $table) {
                $table->string('appearance', 20)->nullable()->default('system')->change();
                $table->string('appearance_sidebar', 20)->nullable()->default('dark')->change();
                $table->string('appearance_header', 20)->nullable()->default('dark')->change();
            });
        }
    }
};
