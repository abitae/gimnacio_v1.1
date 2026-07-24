<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    protected array $tables = [
        'enrollment_installment_plans',
        'enrollment_installments',
        'citas',
        'exercises',
        'routine_templates',
        'discount_coupons',
        'coupon_usages',
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
        'evaluaciones_medidas_nutricion',
        'seguimientos_nutricion',
        'health_records',
        'nutrition_goals',
        'nutrition_goal_progress',
        'cliente_fidelizacion_mensajes',
        'employee_attendances',
        'employee_debts',
        'imports',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sucursal_id')) {
                continue;
            }

            if (DB::table($table)->whereNull('sucursal_id')->exists()) {
                continue;
            }

            $this->enforceNotNullSucursalId($table);
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'sucursal_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                try {
                    $blueprint->unsignedBigInteger('sucursal_id')->nullable()->change();
                } catch (\Throwable) {
                }
            });
        }
    }

    protected function enforceNotNullSucursalId(string $table): void
    {
        if ($this->hasForeignKeyOnSucursalId($table)) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropForeign(['sucursal_id']);
            });
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->unsignedBigInteger('sucursal_id')->nullable(false)->change();
        });

        if ($this->hasForeignKeyOnSucursalId($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('sucursal_id')
                ->references('id')
                ->on('sucursales')
                ->restrictOnDelete();
        });
    }

    protected function hasForeignKeyOnSucursalId(string $table): bool
    {
        try {
            return collect(Schema::getForeignKeys($table))
                ->contains(fn (array $fk) => in_array('sucursal_id', $fk['columns'] ?? [], true));
        } catch (\Throwable) {
            return false;
        }
    }
};
