<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE seguimientos_nutricion
            MODIFY COLUMN tipo ENUM('plan_inicial', 'seguimiento', 'recomendacion', 'incidencia', 'experiencia')
            NOT NULL DEFAULT 'seguimiento'
        ");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("
            ALTER TABLE seguimientos_nutricion
            MODIFY COLUMN tipo ENUM('plan_inicial', 'seguimiento', 'recomendacion')
            NOT NULL DEFAULT 'seguimiento'
        ");
    }
};
