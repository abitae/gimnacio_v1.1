<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            'bio_time_areas',
            'bio_time_departments',
            'bio_time_devices',
            'bio_time_employees',
            'bio_time_transactions',
            'bio_time_sync_logs',
        ] as $table) {
            if (! Schema::hasColumn($table, 'sucursal_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->foreignId('sucursal_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('sucursales')
                        ->nullOnDelete();
                });
            }
        }

        Schema::table('bio_time_sucursal_settings', function (Blueprint $table): void {
            $table->unsignedBigInteger('denied_area_biotime_id')->nullable()->after('area_biotime_id');
            $table->unsignedBigInteger('company_biotime_id')->nullable()->after('denied_area_biotime_id');
            $table->unsignedBigInteger('department_biotime_id')->nullable()->after('company_biotime_id');
            $table->boolean('capacity_enforcement_enabled')->default(false)->after('enabled');
            $table->unsignedBigInteger('config_version')->default(1)->after('employees_count');
        });

        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->boolean('access_enabled')->default(true)->after('access_role');
            $table->unsignedInteger('capacity_limit')->default(500)->after('access_enabled');
            $table->unsignedInteger('reported_users_count')->nullable()->after('capacity_limit');
            $table->unsignedInteger('protected_users_count')->default(0)->after('reported_users_count');
            $table->boolean('inventory_verified')->default(false)->after('protected_users_count');
            $table->string('inventory_source', 50)->nullable()->after('inventory_verified');
            $table->timestamp('inventory_synced_at')->nullable()->after('inventory_source');
        });

        Schema::table('bio_time_access_commands', function (Blueprint $table): void {
            $table->uuid('idempotency_key')->nullable()->unique()->after('id');
            $table->timestamp('leased_at')->nullable()->after('attempts');
            $table->timestamp('lease_expires_at')->nullable()->index()->after('leased_at');
        });

        Schema::create('bio_time_device_users', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bio_time_device_id')->constrained('bio_time_devices')->cascadeOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('emp_code', 50);
            $table->boolean('managed')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['bio_time_device_id', 'emp_code'], 'bio_time_device_users_device_code_unique');
        });

        DB::table('bio_time_access_commands')
            ->whereNull('idempotency_key')
            ->orderBy('id')
            ->eachById(function ($command): void {
                DB::table('bio_time_access_commands')
                    ->where('id', $command->id)
                    ->update(['idempotency_key' => (string) Str::uuid()]);
            });

        $this->backfillSucursalIds();
        $this->replaceGlobalUniqueIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('bio_time_device_users');

        foreach ([
            'bio_time_areas' => 'bio_time_areas_sucursal_biotime_unique',
            'bio_time_departments' => 'bio_time_departments_sucursal_biotime_unique',
            'bio_time_employees' => 'bio_time_employees_sucursal_biotime_unique',
            'bio_time_transactions' => 'bio_time_transactions_sucursal_biotime_unique',
        ] as $table => $index) {
            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropUnique($index);
                $blueprint->unique('biotime_id');
            });
        }

        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->dropUnique('bio_time_devices_sucursal_biotime_unique');
            $table->dropUnique('bio_time_devices_sucursal_serial_unique');
            $table->unique('biotime_id');
            $table->unique('serial_number');
        });

        Schema::table('bio_time_access_commands', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropIndex(['lease_expires_at']);
            $table->dropColumn(['idempotency_key', 'leased_at', 'lease_expires_at']);
        });

        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->dropColumn([
                'access_enabled',
                'capacity_limit',
                'reported_users_count',
                'protected_users_count',
                'inventory_verified',
                'inventory_source',
                'inventory_synced_at',
            ]);
        });

        Schema::table('bio_time_sucursal_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'denied_area_biotime_id',
                'company_biotime_id',
                'department_biotime_id',
                'capacity_enforcement_enabled',
                'config_version',
            ]);
        });

        foreach ([
            'bio_time_sync_logs',
            'bio_time_transactions',
            'bio_time_employees',
            'bio_time_devices',
            'bio_time_departments',
            'bio_time_areas',
        ] as $table) {
            if (Schema::hasColumn($table, 'sucursal_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropConstrainedForeignId('sucursal_id');
                });
            }
        }
    }

    private function backfillSucursalIds(): void
    {
        DB::table('bio_time_devices as d')
            ->join('bio_time_mappings as m', function ($join): void {
                $join->on('m.biotime_id', '=', 'd.biotime_id')
                    ->where('m.mapping_type', '=', 'device');
            })
            ->whereNull('d.sucursal_id')
            ->update(['d.sucursal_id' => DB::raw('m.sucursal_id')]);

        foreach (['area' => 'bio_time_areas', 'department' => 'bio_time_departments'] as $type => $table) {
            DB::table($table.' as c')
                ->join('bio_time_mappings as m', function ($join) use ($type): void {
                    $join->on('m.biotime_id', '=', 'c.biotime_id')
                        ->where('m.mapping_type', '=', $type);
                })
                ->whereNull('c.sucursal_id')
                ->update(['c.sucursal_id' => DB::raw('m.sucursal_id')]);
        }

        DB::table('bio_time_employees as e')
            ->join('clientes as c', 'c.id', '=', 'e.cliente_id')
            ->whereNull('e.sucursal_id')
            ->update(['e.sucursal_id' => DB::raw('c.sucursal_id')]);

        DB::table('bio_time_transactions as t')
            ->join('clientes as c', 'c.id', '=', 't.cliente_id')
            ->whereNull('t.sucursal_id')
            ->update(['t.sucursal_id' => DB::raw('c.sucursal_id')]);

        DB::table('bio_time_sync_logs as l')
            ->join('bio_time_sync_batches as b', 'b.batch_id', '=', 'l.batch_id')
            ->whereNull('l.sucursal_id')
            ->update(['l.sucursal_id' => DB::raw('b.sucursal_id')]);
    }

    private function replaceGlobalUniqueIndexes(): void
    {
        $definitions = [
            'bio_time_areas' => ['bio_time_areas_biotime_id_unique', ['sucursal_id', 'biotime_id'], 'bio_time_areas_sucursal_biotime_unique'],
            'bio_time_departments' => ['bio_time_departments_biotime_id_unique', ['sucursal_id', 'biotime_id'], 'bio_time_departments_sucursal_biotime_unique'],
            'bio_time_employees' => ['bio_time_employees_biotime_id_unique', ['sucursal_id', 'biotime_id'], 'bio_time_employees_sucursal_biotime_unique'],
            'bio_time_transactions' => ['bio_time_transactions_biotime_id_unique', ['sucursal_id', 'biotime_id'], 'bio_time_transactions_sucursal_biotime_unique'],
        ];

        foreach ($definitions as $table => [$oldName, $newColumns, $newName]) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropUnique($oldName));
            if (! $this->indexExists($table, $newName)) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->unique($newColumns, $newName));
            }
        }

        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->dropUnique('bio_time_devices_biotime_id_unique');
            $table->dropUnique('bio_time_devices_serial_number_unique');
        });
        Schema::table('bio_time_devices', function (Blueprint $table): void {
            $table->unique(['sucursal_id', 'biotime_id'], 'bio_time_devices_sucursal_biotime_unique');
            $table->unique(['sucursal_id', 'serial_number'], 'bio_time_devices_sucursal_serial_unique');
        });
    }

    private function indexExists(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn (array $index) => ($index['name'] ?? null) === $name);
    }
};
