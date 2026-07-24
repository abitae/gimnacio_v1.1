<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $principalId = DB::table('sucursales')
            ->where('estado', 'activa')
            ->orderByDesc('es_principal')
            ->value('id');

        if ($principalId === null) {
            return;
        }

        $this->addSucursalColumn('enrollment_installment_plans', $principalId, function () {
            DB::table('enrollment_installment_plans as p')
                ->join('cliente_matriculas as m', 'm.id', '=', 'p.cliente_matricula_id')
                ->whereNull('p.sucursal_id')
                ->update(['p.sucursal_id' => DB::raw('m.sucursal_id')]);
        });

        $this->addSucursalColumn('enrollment_installments', $principalId, function () {
            DB::table('enrollment_installments as i')
                ->join('cliente_matriculas as m', 'm.id', '=', 'i.cliente_matricula_id')
                ->whereNull('i.sucursal_id')
                ->update(['i.sucursal_id' => DB::raw('m.sucursal_id')]);
        });

        $this->addSucursalColumn('citas', $principalId, function () {
            DB::table('citas as c')
                ->join('clientes as cl', 'cl.id', '=', 'c.cliente_id')
                ->whereNull('c.sucursal_id')
                ->update(['c.sucursal_id' => DB::raw('cl.sucursal_id')]);
        });

        $this->addSucursalColumn('exercises', $principalId);
        $this->addSucursalColumn('routine_templates', $principalId);

        $this->backfillNullableSucursalId($principalId);

        if (Schema::hasTable('discount_coupons') && Schema::hasColumn('discount_coupons', 'codigo')) {
            Schema::table('discount_coupons', function (Blueprint $table) {
                foreach ($this->uniqueIndexesOnColumn('discount_coupons', 'codigo') as $indexName) {
                    try {
                        $table->dropUnique($indexName);
                    } catch (\Throwable) {
                    }
                }

                if (! $this->indexExists('discount_coupons', 'discount_coupons_sucursal_codigo_unique')) {
                    $table->unique(['sucursal_id', 'codigo'], 'discount_coupons_sucursal_codigo_unique');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['enrollment_installment_plans', 'enrollment_installments', 'citas', 'exercises', 'routine_templates'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sucursal_id')) {
                Schema::table($table, function (Blueprint $blueprint) use ($table) {
                    $blueprint->dropConstrainedForeignId('sucursal_id');
                });
            }
        }
    }

    protected function addSucursalColumn(string $table, int $fallbackId, ?callable $backfill = null): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'sucursal_id')) {
            if ($backfill) {
                $backfill();
            }

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreignId('sucursal_id')
                ->nullable()
                ->after('id')
                ->constrained('sucursales')
                ->restrictOnDelete();
        });

        DB::table($table)->whereNull('sucursal_id')->update(['sucursal_id' => $fallbackId]);

        if ($backfill) {
            $backfill();
        }

        DB::table($table)->whereNull('sucursal_id')->update(['sucursal_id' => $fallbackId]);
    }

    protected function backfillNullableSucursalId(int $principalId): void
    {
        $tables = [
            'discount_coupons', 'coupon_usages', 'crm_leads', 'crm_deals', 'crm_activities', 'crm_tasks',
            'crm_campaigns', 'crm_campaign_targets', 'crm_mensajes', 'tags', 'crm_stages', 'loss_reasons',
            'evaluaciones_medidas_nutricion', 'seguimientos_nutricion', 'health_records', 'nutrition_goals',
            'nutrition_goal_progress', 'cliente_fidelizacion_mensajes', 'employee_attendances', 'employee_debts',
            'imports',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'sucursal_id')) {
                DB::table($table)->whereNull('sucursal_id')->update(['sucursal_id' => $principalId]);
            }
        }
    }

    protected function indexExists(string $table, string $indexName): bool
    {
        try {
            return collect(Schema::getIndexes($table))
                ->contains(fn (array $index) => ($index['name'] ?? '') === $indexName);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function uniqueIndexesOnColumn(string $table, string $column): array
    {
        try {
            return collect(Schema::getIndexes($table))
                ->filter(fn (array $index) => ($index['unique'] ?? false) === true)
                ->filter(fn (array $index) => in_array($column, $index['columns'] ?? [], true))
                ->pluck('name')
                ->filter(fn (?string $name) => $name !== null && $name !== 'PRIMARY')
                ->unique()
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }
};
