<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected function isSqlite(): bool
    {
        return Schema::getConnection()->getDriverName() === 'sqlite';
    }

    public function up(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        DB::statement("ALTER TABLE venta_items MODIFY COLUMN tipo_item ENUM('producto', 'servicio', 'clase', 'alquiler') NOT NULL");
    }

    public function down(): void
    {
        if ($this->isSqlite()) {
            return;
        }

        DB::statement("ALTER TABLE venta_items MODIFY COLUMN tipo_item ENUM('producto', 'servicio', 'clase') NOT NULL");
    }
};
