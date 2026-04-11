<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Índices compuestos alineados con filtros frecuentes (sucursal, fechas, deuda).
     */
    public function up(): void
    {
        if (Schema::hasTable('pagos') && Schema::hasColumn('pagos', 'sucursal_id')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->index(['sucursal_id', 'fecha_pago'], 'pagos_sucursal_id_fecha_pago_index');
                $table->index(['cliente_id', 'saldo_pendiente'], 'pagos_cliente_id_saldo_pendiente_index');
            });
        }

        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'sucursal_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->index(['sucursal_id', 'estado_cliente'], 'clientes_sucursal_id_estado_cliente_index');
                $table->index(['sucursal_id', 'created_at'], 'clientes_sucursal_id_created_at_index');
            });
        }

        if (Schema::hasTable('caja_movimientos') && Schema::hasColumn('caja_movimientos', 'sucursal_id')) {
            Schema::table('caja_movimientos', function (Blueprint $table) {
                $table->index(['sucursal_id', 'fecha_movimiento'], 'caja_movimientos_sucursal_id_fecha_movimiento_index');
            });
        }

        if (Schema::hasTable('employee_attendances') && Schema::hasColumn('employee_attendances', 'sucursal_id')) {
            Schema::table('employee_attendances', function (Blueprint $table) {
                $table->index(['sucursal_id', 'fecha'], 'employee_attendances_sucursal_id_fecha_index');
            });
        }

        if (Schema::hasTable('enrollment_installments')) {
            Schema::table('enrollment_installments', function (Blueprint $table) {
                $table->index(['fecha_vencimiento', 'estado'], 'enrollment_installments_fecha_vencimiento_estado_index');
            });
        }

        if (Schema::hasTable('rentals') && Schema::hasColumn('rentals', 'sucursal_id')) {
            Schema::table('rentals', function (Blueprint $table) {
                $table->index(['sucursal_id', 'fecha', 'estado'], 'rentals_sucursal_id_fecha_estado_index');
            });
        }

        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'sucursal_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->index(['sucursal_id', 'fecha_venta'], 'ventas_sucursal_id_fecha_venta_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pagos') && Schema::hasColumn('pagos', 'sucursal_id')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropIndex('pagos_sucursal_id_fecha_pago_index');
                $table->dropIndex('pagos_cliente_id_saldo_pendiente_index');
            });
        }

        if (Schema::hasTable('clientes') && Schema::hasColumn('clientes', 'sucursal_id')) {
            Schema::table('clientes', function (Blueprint $table) {
                $table->dropIndex('clientes_sucursal_id_estado_cliente_index');
                $table->dropIndex('clientes_sucursal_id_created_at_index');
            });
        }

        if (Schema::hasTable('caja_movimientos') && Schema::hasColumn('caja_movimientos', 'sucursal_id')) {
            Schema::table('caja_movimientos', function (Blueprint $table) {
                $table->dropIndex('caja_movimientos_sucursal_id_fecha_movimiento_index');
            });
        }

        if (Schema::hasTable('employee_attendances') && Schema::hasColumn('employee_attendances', 'sucursal_id')) {
            Schema::table('employee_attendances', function (Blueprint $table) {
                $table->dropIndex('employee_attendances_sucursal_id_fecha_index');
            });
        }

        if (Schema::hasTable('enrollment_installments')) {
            Schema::table('enrollment_installments', function (Blueprint $table) {
                $table->dropIndex('enrollment_installments_fecha_vencimiento_estado_index');
            });
        }

        if (Schema::hasTable('rentals') && Schema::hasColumn('rentals', 'sucursal_id')) {
            Schema::table('rentals', function (Blueprint $table) {
                $table->dropIndex('rentals_sucursal_id_fecha_estado_index');
            });
        }

        if (Schema::hasTable('ventas') && Schema::hasColumn('ventas', 'sucursal_id')) {
            Schema::table('ventas', function (Blueprint $table) {
                $table->dropIndex('ventas_sucursal_id_fecha_venta_index');
            });
        }
    }
};
