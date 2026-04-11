<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'clientes',
            'membresias',
            'clases',
            'cajas',
            'cliente_membresias',
            'cliente_matriculas',
            'pagos',
            'asistencias',
            'ventas',
            'caja_movimientos',
            'categorias_productos',
            'productos',
            'categorias_servicios',
            'servicios_externos',
            'payment_methods',
            'discount_coupons',
            'coupon_usages',
            'employees',
            'employee_attendances',
            'employee_debts',
            'rentable_spaces',
            'rentable_space_rates',
            'rentals',
            'rental_payments',
            'evaluaciones_medidas_nutricion',
            'seguimientos_nutricion',
            'health_records',
            'nutrition_goals',
            'nutrition_goal_progress',
            'cliente_plan_traspasos',
            'cliente_fidelizacion_mensajes',
            'crm_leads',
            'crm_deals',
            'crm_activities',
            'crm_tasks',
            'crm_campaigns',
            'crm_campaign_targets',
            'crm_mensajes',
            'tags',
            'crm_stages',
            'loss_reasons',
            'comprobantes_config',
            'cobro_tickets_secuencias',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'sucursal_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('sucursal_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('sucursales')
                    ->nullOnDelete();
            });
        }

        $empresaId = DB::table('empresas')->insertGetId([
            'nombre' => DB::table('gym_settings')->value('nombre_gimnasio') ?: config('app.name', 'Open9'),
            'razon_social' => DB::table('gym_settings')->value('nombre_gimnasio') ?: config('app.name', 'Open9'),
            'ruc' => DB::table('gym_settings')->value('ruc'),
            'direccion' => DB::table('gym_settings')->value('direccion'),
            'telefono' => DB::table('gym_settings')->value('telefono'),
            'email' => DB::table('gym_settings')->value('email'),
            'logo' => DB::table('gym_settings')->value('logo'),
            'estado' => 'activa',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $codigoPrincipal = 'principal';
        $existingCodigo = DB::table('sucursales')->where('codigo', $codigoPrincipal)->exists();

        if ($existingCodigo) {
            $codigoPrincipal = 'principal-'.Str::random(4);
        }

        $sucursalId = DB::table('sucursales')->insertGetId([
            'empresa_id' => $empresaId,
            'codigo' => $codigoPrincipal,
            'nombre' => DB::table('gym_settings')->value('nombre_gimnasio') ?: 'Sucursal Principal',
            'direccion' => DB::table('gym_settings')->value('direccion'),
            'telefono' => DB::table('gym_settings')->value('telefono'),
            'email' => DB::table('gym_settings')->value('email'),
            'logo' => DB::table('gym_settings')->value('logo'),
            'estado' => 'activa',
            'es_principal' => true,
            'horarios_acceso' => DB::table('gym_settings')->value('horarios_acceso'),
            'politicas_acceso' => DB::table('gym_settings')->value('politicas_acceso'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sucursal_id')) {
                continue;
            }

            DB::table($table)
                ->whereNull('sucursal_id')
                ->update(['sucursal_id' => $sucursalId]);
        }

        $userIds = DB::table('users')->pluck('id');

        foreach ($userIds as $userId) {
            DB::table('sucursal_user')->updateOrInsert(
                ['sucursal_id' => $sucursalId, 'user_id' => $userId],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        DB::table('users')
            ->whereNull('default_sucursal_id')
            ->update(['default_sucursal_id' => $sucursalId]);

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropUnique('clientes_tipo_documento_numero_documento_unique');
                $table->unique(['sucursal_id', 'tipo_documento', 'numero_documento'], 'clientes_sucursal_documento_unique');
            });
        }

        if (Schema::hasTable('clases')) {
            Schema::table('clases', function (Blueprint $table) {
                $table->dropUnique('clases_codigo_unique');
                $table->unique(['sucursal_id', 'codigo'], 'clases_sucursal_codigo_unique');
            });
        }

        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropUnique('ventas_numero_venta_unique');
                $table->unique(['sucursal_id', 'numero_venta'], 'ventas_sucursal_numero_venta_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventas')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropUnique('ventas_sucursal_numero_venta_unique');
                $table->unique('numero_venta');
            });
        }

        if (Schema::hasTable('clases')) {
            Schema::table('clases', function (Blueprint $table) {
                $table->dropUnique('clases_sucursal_codigo_unique');
                $table->unique('codigo');
            });
        }

        if (Schema::hasTable('clientes')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropUnique('clientes_sucursal_documento_unique');
                $table->unique(['tipo_documento', 'numero_documento']);
            });
        }

        $tables = [
            'clientes',
            'membresias',
            'clases',
            'cajas',
            'cliente_membresias',
            'cliente_matriculas',
            'pagos',
            'asistencias',
            'ventas',
            'caja_movimientos',
            'categorias_productos',
            'productos',
            'categorias_servicios',
            'servicios_externos',
            'payment_methods',
            'discount_coupons',
            'coupon_usages',
            'employees',
            'employee_attendances',
            'employee_debts',
            'rentable_spaces',
            'rentable_space_rates',
            'rentals',
            'rental_payments',
            'evaluaciones_medidas_nutricion',
            'seguimientos_nutricion',
            'health_records',
            'nutrition_goals',
            'nutrition_goal_progress',
            'cliente_plan_traspasos',
            'cliente_fidelizacion_mensajes',
            'crm_leads',
            'crm_deals',
            'crm_activities',
            'crm_tasks',
            'crm_campaigns',
            'crm_campaign_targets',
            'crm_mensajes',
            'tags',
            'crm_stages',
            'loss_reasons',
            'comprobantes_config',
            'cobro_tickets_secuencias',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sucursal_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropConstrainedForeignId('sucursal_id');
            });
        }
    }
};
