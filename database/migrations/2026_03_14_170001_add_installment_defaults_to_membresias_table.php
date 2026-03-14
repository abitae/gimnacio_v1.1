<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            $table->boolean('permite_cuotas')->default(false)->after('precio_base');
            $table->unsignedSmallInteger('numero_cuotas_default')->nullable()->after('permite_cuotas');
            $table->string('frecuencia_cuotas_default', 20)->nullable()->after('numero_cuotas_default');
            $table->decimal('cuota_inicial_monto', 12, 2)->nullable()->after('frecuencia_cuotas_default');
            $table->decimal('cuota_inicial_porcentaje', 5, 2)->nullable()->after('cuota_inicial_monto');
        });
    }

    public function down(): void
    {
        Schema::table('membresias', function (Blueprint $table) {
            $table->dropColumn([
                'permite_cuotas',
                'numero_cuotas_default',
                'frecuencia_cuotas_default',
                'cuota_inicial_monto',
                'cuota_inicial_porcentaje',
            ]);
        });
    }
};
