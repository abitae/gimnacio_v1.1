<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\BioTime\BioTimeArea;
use App\Models\BioTime\BioTimeDepartment;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeMapping;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Models\BioTime\BioTimeSyncLog;
use App\Models\BioTime\BioTimeTransaction;
use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class BioTimeSyncService
{
    private int $sucursalId;

    /**
     * @return array{processed:int, failed:int, errors:list<string>}
     */
    public function process(string $entity, string $timestamp, array $records, string $batchId, ?int $sucursalId = null): array
    {
        $resolvedSucursalId = $sucursalId;
        if (! $resolvedSucursalId) {
            $resolvedSucursalId = (int) (BioTimeSyncBatch::query()
                ->where('batch_id', $batchId)
                ->value('sucursal_id') ?: 0);
        }
        if (! $resolvedSucursalId) {
            $empCodes = collect($records)
                ->pluck('emp_code')
                ->filter()
                ->map(fn ($code) => (string) $code)
                ->unique()
                ->values();
            $candidateSucursales = Cliente::query()
                ->withoutGlobalScope('active_sucursal')
                ->where(function ($query) use ($empCodes): void {
                    $query->whereIn('numero_documento', $empCodes)
                        ->orWhereIn('codigo', $empCodes);
                })
                ->distinct()
                ->pluck('sucursal_id');
            if ($candidateSucursales->count() === 1) {
                $resolvedSucursalId = (int) $candidateSucursales->first();
            }
        }
        if (! $resolvedSucursalId) {
            $resolvedSucursalId = (int) (\App\Models\BioTime\BioTimeSucursalSetting::query()
                ->orderBy('id')
                ->value('sucursal_id') ?: 0);
        }
        if (! $resolvedSucursalId) {
            $resolvedSucursalId = (int) (\App\Models\System\Sucursal::query()
                ->orderByDesc('es_principal')
                ->orderBy('id')
                ->value('id') ?: 0);
        }
        $this->sucursalId = (int) $resolvedSucursalId;

        if ($this->sucursalId <= 0) {
            throw new RuntimeException('No se pudo resolver la sucursal del lote BioTime.');
        }
        $processed = 0;
        $failed = 0;
        $errors = [];

        DB::transaction(function () use ($entity, $timestamp, $records, $batchId, &$processed, &$failed, &$errors): void {
            foreach ($records as $index => $row) {
                if (! is_array($row)) {
                    $failed++;
                    $errors[] = "Registro #{$index}: no es un objeto JSON valido.";
                    $this->log($batchId, $entity, null, 'failed', null, null, $row, $errors[array_key_last($errors)]);

                    continue;
                }

                try {
                    $result = match ($entity) {
                        'departments' => $this->upsertDepartment($row, $timestamp),
                        'areas' => $this->upsertArea($row, $timestamp),
                        'devices' => $this->upsertDevice($row, $timestamp),
                        'employees' => $this->upsertEmployee($row, $timestamp),
                        'transactions' => $this->upsertTransaction($row, $timestamp),
                        default => throw new RuntimeException("Entidad no soportada: {$entity}"),
                    };

                    $processed++;
                    $this->log($batchId, $entity, $result['biotime_id'], $result['status'], $result['action'], null, $row, $result['message'] ?? null);
                } catch (Throwable $e) {
                    $failed++;
                    $errors[] = "Registro #{$index}: {$e->getMessage()}";
                    $this->log($batchId, $entity, $this->nullableInt($row['id'] ?? null), 'failed', null, null, $row, $e->getMessage());
                }
            }

            BioTimeSyncBatch::query()
                ->where('batch_id', $batchId)
                ->update([
                    'status' => $failed > 0 ? ($processed > 0 ? 'partial' : 'failed') : 'success',
                    'processed' => $processed,
                    'failed' => $failed,
                    'processed_at' => now(),
                    'error_message' => $errors === [] ? null : implode("\n", array_slice($errors, 0, 10)),
                ]);
        });

        return ['processed' => $processed, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * @return array{biotime_id:int,status:string,action:string}
     */
    private function upsertDepartment(array $row, string $timestamp): array
    {
        $biotimeId = $this->requiredBioTimeId($row, 'department.id requerido');

        $model = BioTimeDepartment::query()->updateOrCreate(
            ['sucursal_id' => $this->sucursalId, 'biotime_id' => $biotimeId],
            [
                'sucursal_id' => $this->sucursalId,
                'dept_code' => $this->nullableString($row['dept_code'] ?? null),
                'dept_name' => $this->nullableString($row['dept_name'] ?? null),
                'parent_biotime_id' => $this->nullableInt($row['parent_dept'] ?? null),
                'raw_payload' => $row,
                'synced_at' => $this->parseDate($timestamp) ?? now(),
            ]
        );

        return $this->result($biotimeId, 'success', $model);
    }

    /**
     * @return array{biotime_id:int,status:string,action:string}
     */
    private function upsertArea(array $row, string $timestamp): array
    {
        $biotimeId = $this->requiredBioTimeId($row, 'area.id requerido');

        $model = BioTimeArea::query()->updateOrCreate(
            ['sucursal_id' => $this->sucursalId, 'biotime_id' => $biotimeId],
            [
                'sucursal_id' => $this->sucursalId,
                'area_code' => $this->nullableString($row['area_code'] ?? null),
                'area_name' => $this->nullableString($row['area_name'] ?? null),
                'parent_biotime_id' => $this->nullableInt($row['parent_area'] ?? null),
                'raw_payload' => $row,
                'synced_at' => $this->parseDate($timestamp) ?? now(),
            ]
        );

        return $this->result($biotimeId, 'success', $model);
    }

    /**
     * @return array{biotime_id:int,status:string,action:string}
     */
    private function upsertDevice(array $row, string $timestamp): array
    {
        $biotimeId = $this->nullableInt($row['id'] ?? null);
        $serial = $this->nullableString($row['sn'] ?? null);

        if ($biotimeId === null && $serial === null) {
            throw new RuntimeException('device.id o sn requerido');
        }

        $area = is_array($row['area'] ?? null) ? $row['area'] : null;
        $model = BioTimeDevice::query()
            ->where('sucursal_id', $this->sucursalId)
            ->where(function ($query) use ($biotimeId, $serial): void {
                if ($biotimeId !== null) {
                    $query->where('biotime_id', $biotimeId);
                }

                if ($serial !== null) {
                    $method = $biotimeId === null ? 'where' : 'orWhere';
                    $query->{$method}('serial_number', $serial);
                }
            })
            ->first();

        if (! $model) {
            $model = new BioTimeDevice;
        }

        $model->fill([
            'sucursal_id' => $this->sucursalId,
            'biotime_id' => $biotimeId,
            'serial_number' => $serial,
            'alias' => $this->nullableString($row['alias'] ?? null),
            'ip_address' => $this->nullableString($row['ip_address'] ?? null),
            'state' => $this->nullableInt($row['state'] ?? null),
            'area_biotime_id' => $area ? $this->nullableInt($area['id'] ?? null) : $this->nullableInt($row['area'] ?? null),
            'last_activity' => $this->parseDate($row['last_activity'] ?? null),
            'is_attendance' => (bool) ($row['is_attendance'] ?? false),
            'raw_payload' => $row,
            'synced_at' => $this->parseDate($timestamp) ?? now(),
        ])->save();

        return $this->result($biotimeId ?? (int) $model->id, 'success', $model);
    }

    /**
     * @return array{biotime_id:int,status:string,action:string,message?:string}
     */
    private function upsertEmployee(array $row, string $timestamp): array
    {
        $biotimeId = $this->nullableInt($row['id'] ?? null);
        $empCode = $this->nullableString($row['emp_code'] ?? null);

        if ($biotimeId === null && $empCode === null) {
            throw new RuntimeException('employee.id o emp_code requerido');
        }

        $this->upsertCatalogFromEmployee($row, $timestamp);

        $department = is_array($row['department'] ?? null) ? $row['department'] : null;
        $departmentId = $department ? $this->nullableInt($department['id'] ?? null) : $this->nullableInt($row['department'] ?? null);
        $areaIds = $this->areaIds($row['area'] ?? []);
        $cliente = $this->resolveClienteForEmployee($biotimeId, $empCode, $departmentId, $areaIds);

        $model = BioTimeEmployee::query()
            ->where('sucursal_id', $this->sucursalId)
            ->where(function ($query) use ($biotimeId, $empCode): void {
                if ($biotimeId !== null) {
                    $query->where('biotime_id', $biotimeId);
                }

                if ($empCode !== null) {
                    $method = $biotimeId === null ? 'where' : 'orWhere';
                    $query->{$method}('emp_code', $empCode);
                }
            })
            ->first();

        if (! $model) {
            $model = new BioTimeEmployee;
        }

        $model->fill([
            'sucursal_id' => $this->sucursalId,
            'biotime_id' => $biotimeId,
            'emp_code' => $empCode,
            'cliente_id' => $cliente?->id,
            'first_name' => $this->nullableString($row['first_name'] ?? null),
            'last_name' => $this->nullableString($row['last_name'] ?? null),
            'department_biotime_id' => $departmentId,
            'department_name' => $department ? $this->nullableString($department['dept_name'] ?? null) : $this->nullableString($row['dept_name'] ?? null),
            'app_status' => $this->nullableInt($row['app_status'] ?? null),
            'mobile' => $this->nullableString($row['mobile'] ?? null),
            'email' => $this->nullableString($row['email'] ?? null),
            'hire_date' => $this->parseDate($row['hire_date'] ?? null)?->toDateString(),
            'card_no' => $this->nullableString($row['card_no'] ?? null),
            'area_biotime_ids' => $areaIds,
            'raw_payload' => $row,
            'synced_at' => $this->parseDate($timestamp) ?? now(),
        ])->save();

        if ($cliente && $biotimeId !== null && (int) ($cliente->biotime_id ?? 0) !== $biotimeId) {
            $cliente->forceFill(['biotime_id' => $biotimeId])->save();
        }

        return [
            'biotime_id' => $biotimeId ?? (int) $model->id,
            'status' => $cliente ? 'success' : 'pending',
            'action' => $model->wasRecentlyCreated ? 'created' : 'updated',
            'message' => $cliente ? null : "Cliente no encontrado para codigo {$empCode}.",
        ];
    }

    /**
     * @return array{biotime_id:int,status:string,action:string,message?:string}
     */
    private function upsertTransaction(array $row, string $timestamp): array
    {
        $biotimeId = $this->requiredBioTimeId($row, 'transaction.id requerido');
        $empCode = $this->nullableString($row['emp_code'] ?? null);
        $terminalSn = $this->nullableString($row['terminal_sn'] ?? null);
        $departmentId = $this->nullableInt($row['department_id'] ?? null);
        $punchTime = $this->parseDate($row['punch_time'] ?? null);
        $cliente = $this->resolveClienteForTransaction($empCode, $terminalSn, $departmentId);

        $existing = BioTimeTransaction::query()
            ->where('sucursal_id', $this->sucursalId)
            ->where('biotime_id', $biotimeId)
            ->first();
        $asistencia = null;
        $message = null;

        if ($existing?->asistencia_id) {
            $asistencia = Asistencia::query()->find($existing->asistencia_id);
        } elseif ($cliente && $punchTime) {
            $device = $terminalSn
                ? $this->findDeviceBySerial($terminalSn)
                : null;
            $syncResult = $this->syncAsistencia($cliente, $device, $punchTime);
            $asistencia = $syncResult['asistencia'];
            $message = $syncResult['message'];
        } elseif (! $cliente) {
            $message = "Marcacion sin cliente enlazado para codigo {$empCode}.";
        }

        $model = BioTimeTransaction::query()->updateOrCreate(
            ['sucursal_id' => $this->sucursalId, 'biotime_id' => $biotimeId],
            [
                'sucursal_id' => $this->sucursalId,
                'cliente_id' => $cliente?->id,
                'asistencia_id' => $asistencia?->id ?? $existing?->asistencia_id,
                'emp_code' => $empCode,
                'punch_time' => $punchTime,
                'punch_state' => $this->nullableString($row['punch_state'] ?? null),
                'punch_state_display' => $this->nullableString($row['punch_state_display'] ?? null),
                'verify_type' => $this->nullableInt($row['verify_type'] ?? null),
                'terminal_sn' => $terminalSn,
                'terminal_alias' => $this->nullableString($row['terminal_alias'] ?? null),
                'upload_time' => $this->parseDate($row['upload_time'] ?? null),
                'department_name' => $this->nullableString($row['department'] ?? null),
                'position_name' => $this->nullableString($row['position'] ?? null),
                'raw_payload' => $row,
                'synced_at' => $this->parseDate($timestamp) ?? now(),
            ]
        );

        $status = 'success';
        if (! $cliente) {
            $status = 'pending';
        } elseif ($message !== null && $asistencia === null) {
            $status = 'pending';
        }

        return [
            'biotime_id' => $biotimeId,
            'status' => $status,
            'action' => $model->wasRecentlyCreated ? 'created' : 'updated',
            'message' => $message,
        ];
    }

    /**
     * Direccion por access_role del terminal (ignora punch_state).
     *
     * @return array{asistencia: ?Asistencia, message: ?string}
     */
    private function syncAsistencia(Cliente $cliente, ?BioTimeDevice $device, Carbon $punchTime): array
    {
        $direction = $this->resolvePunchDirection($device, $cliente);

        if ($direction === null) {
            return [
                'asistencia' => null,
                'message' => 'Terminal sin rol de acceso; transaccion guardada sin asistencia.',
            ];
        }

        $open = Asistencia::query()
            ->where('cliente_id', $cliente->id)
            ->whereNull('fecha_hora_salida')
            ->orderByDesc('fecha_hora_ingreso')
            ->first();

        $sucursalId = $this->resolveSucursalId(
            $device,
            $device?->area_biotime_id ? [(int) $device->area_biotime_id] : [],
            null
        ) ?? $cliente->sucursal_id;

        if ($direction === 'exit') {
            if (! $open) {
                return [
                    'asistencia' => null,
                    'message' => 'Salida ignorada: no hay ingreso abierto.',
                ];
            }

            $open->forceFill([
                'fecha_hora_salida' => $punchTime,
                'checkout_origen' => 'biotime',
            ])->save();

            return ['asistencia' => $open, 'message' => null];
        }

        $asistencia = Asistencia::query()->create([
            'cliente_id' => $cliente->id,
            'fecha_hora_ingreso' => $punchTime,
            'origen' => 'biotime',
            'valido_por_membresia' => true,
            'registrada_por' => null,
            'sucursal_id' => $sucursalId,
        ]);

        return ['asistencia' => $asistencia, 'message' => null];
    }

    /**
     * @return 'entry'|'exit'|null null = no generar asistencia
     */
    private function resolvePunchDirection(?BioTimeDevice $device, Cliente $cliente): ?string
    {
        $role = $device?->access_role;
        if ($role === null || $role === '') {
            return null;
        }

        return match ($role) {
            BioTimeDevice::ACCESS_ROLE_ENTRADA => 'entry',
            BioTimeDevice::ACCESS_ROLE_SALIDA => 'exit',
            BioTimeDevice::ACCESS_ROLE_AMBOS => Asistencia::query()
                ->where('cliente_id', $cliente->id)
                ->whereNull('fecha_hora_salida')
                ->exists() ? 'exit' : 'entry',
            default => null,
        };
    }

    private function resolveClienteForEmployee(?int $biotimeId, ?string $empCode, ?int $departmentId, array $areaIds): ?Cliente
    {
        if ($biotimeId !== null) {
            $mapping = BioTimeMapping::query()
                ->where('sucursal_id', $this->sucursalId)
                ->where('mapping_type', 'employee')
                ->where('biotime_id', $biotimeId)
                ->where('target_type', 'cliente')
                ->first();

            if ($mapping) {
                return Cliente::query()
                    ->withoutGlobalScope('active_sucursal')
                    ->find($mapping->target_id);
            }
        }

        if ($empCode === null) {
            return null;
        }

        $sucursalId = $this->resolveSucursalId(null, $areaIds, $departmentId);

        return $this->findClienteByEmpCode($empCode, $sucursalId);
    }

    private function resolveClienteForTransaction(?string $empCode, ?string $terminalSn, ?int $departmentId): ?Cliente
    {
        if ($empCode === null) {
            return null;
        }

        $employee = BioTimeEmployee::query()
            ->where('sucursal_id', $this->sucursalId)
            ->where('emp_code', $empCode)
            ->whereNotNull('cliente_id')
            ->first();

        if ($employee?->cliente_id) {
            return Cliente::query()->find($employee->cliente_id);
        }

        $device = $terminalSn
            ? $this->findDeviceBySerial($terminalSn)
            : null;
        $sucursalId = $this->resolveSucursalId($device, $device?->area_biotime_id ? [(int) $device->area_biotime_id] : [], $departmentId);

        return $this->findClienteByEmpCode($empCode, $sucursalId);
    }

    private function resolveSucursalId(?BioTimeDevice $device, array $areaIds, ?int $departmentId): ?int
    {
        if ($device) {
            $deviceMapping = BioTimeMapping::query()
                ->where('sucursal_id', $this->sucursalId)
                ->where('mapping_type', 'device')
                ->where('biotime_id', $device->biotime_id ?? $device->id)
                ->first();

            if ($deviceMapping?->target_type === 'sucursal') {
                return (int) $deviceMapping->target_id;
            }
        }

        foreach ($areaIds as $areaId) {
            $mapping = BioTimeMapping::query()
                ->where('sucursal_id', $this->sucursalId)
                ->where('mapping_type', 'area')
                ->where('biotime_id', $areaId)
                ->first();

            if ($mapping?->target_type === 'sucursal') {
                return (int) $mapping->target_id;
            }
        }

        if ($departmentId !== null) {
            $mapping = BioTimeMapping::query()
                ->where('sucursal_id', $this->sucursalId)
                ->where('mapping_type', 'department')
                ->where('biotime_id', $departmentId)
                ->first();

            if ($mapping?->target_type === 'sucursal') {
                return (int) $mapping->target_id;
            }
        }

        // El token del puente es la fuente autoritativa de la sede. Los mapeos
        // solo refinan catalogos legacy y nunca deben sacar un registro de ella.
        return $this->sucursalId;
    }

    /**
     * Sync inbound BioTime: emp_code del reloj → cliente Laravel por numero_documento.
     */
    private function findClienteByEmpCode(string $empCode, ?int $sucursalId): ?Cliente
    {
        $base = Cliente::query()->withoutGlobalScope('active_sucursal');

        if ($sucursalId !== null) {
            return (clone $base)
                ->where('sucursal_id', $sucursalId)
                ->where('numero_documento', $empCode)
                ->first();
        }

        $byDocumento = (clone $base)->where('numero_documento', $empCode)->limit(2)->get();

        return $byDocumento->count() === 1 ? $byDocumento->first() : null;
    }

    private function findDeviceBySerial(string $serial): ?BioTimeDevice
    {
        $device = BioTimeDevice::query()
            ->where('sucursal_id', $this->sucursalId)
            ->where('serial_number', $serial)
            ->first();

        if ($device) {
            return $device;
        }

        // Compatibilidad con espejos creados antes de la migracion multi-sede.
        $legacy = BioTimeDevice::query()
            ->whereNull('sucursal_id')
            ->where('serial_number', $serial)
            ->first();
        if ($legacy) {
            $legacy->forceFill(['sucursal_id' => $this->sucursalId])->save();
        }

        return $legacy;
    }

    private function log(string $batchId, string $entity, ?int $biotimeId, string $status, ?string $action, ?string $target, mixed $payload, ?string $error): void
    {
        BioTimeSyncLog::query()->create([
            'sucursal_id' => $this->sucursalId,
            'batch_id' => $batchId,
            'entity' => $entity,
            'biotime_id' => $biotimeId,
            'status' => $status,
            'action' => $action ?? $target,
            'payload' => is_array($payload) ? $payload : ['value' => $payload],
            'error_message' => $error,
            'processed_at' => now(),
        ]);
    }

    /**
     * Extrae areas/departamentos embebidos en el payload de employee para el mapeo UI.
     */
    private function upsertCatalogFromEmployee(array $row, string $timestamp): void
    {
        $department = is_array($row['department'] ?? null) ? $row['department'] : null;
        if (is_array($department) && $this->nullableInt($department['id'] ?? null) !== null) {
            $this->upsertDepartment([
                'id' => $department['id'],
                'dept_code' => $department['dept_code'] ?? null,
                'dept_name' => $department['dept_name'] ?? null,
                'parent_dept' => $department['parent_dept'] ?? null,
            ], $timestamp);
        }

        $areas = $row['area'] ?? [];
        if (! is_array($areas)) {
            return;
        }

        foreach ($areas as $area) {
            if (! is_array($area)) {
                $areaId = $this->nullableInt($area);
                if ($areaId === null) {
                    continue;
                }
                $this->upsertArea(['id' => $areaId], $timestamp);

                continue;
            }

            if ($this->nullableInt($area['id'] ?? null) === null) {
                continue;
            }

            $this->upsertArea([
                'id' => $area['id'],
                'area_code' => $area['area_code'] ?? null,
                'area_name' => $area['area_name'] ?? null,
                'parent_area' => $area['parent_area'] ?? null,
            ], $timestamp);
        }
    }

    /**
     * @return array<int>
     */
    private function areaIds(mixed $areas): array
    {
        if (! is_array($areas)) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn (mixed $area): ?int => is_array($area) ? $this->nullableInt($area['id'] ?? null) : $this->nullableInt($area),
            $areas
        )));
    }

    private function isExitPunch(array $row): bool
    {
        // Legacy: la direccion de asistencia ahora usa BioTimeDevice.access_role.
        $state = strtolower((string) ($row['punch_state'] ?? ''));
        $display = strtolower((string) ($row['punch_state_display'] ?? ''));

        return in_array($state, ['1', 'out', 'exit', 'check_out', 'checkout'], true)
            || str_contains($display, 'salida')
            || str_contains($display, 'out')
            || str_contains($display, 'exit');
    }

    private function requiredBioTimeId(array $row, string $message): int
    {
        $id = $this->nullableInt($row['id'] ?? null);

        if ($id === null || $id <= 0) {
            throw new RuntimeException($message);
        }

        return $id;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $this->nullableInt($value['id'] ?? null);
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{biotime_id:int,status:string,action:string}
     */
    private function result(int $biotimeId, string $status, Model $model): array
    {
        return [
            'biotime_id' => $biotimeId,
            'status' => $status,
            'action' => $model->wasRecentlyCreated ? 'created' : 'updated',
        ];
    }

    /**
     * @return array{received_at: ?Carbon, status: string, entity: ?string, processed: int, failed: int}|null
     */
    public function lastSyncSummary(?int $sucursalId = null): ?array
    {
        $batch = BioTimeSyncBatch::query()
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->orderByDesc('received_at')
            ->first();
        if (! $batch) {
            return null;
        }

        return [
            'received_at' => $batch->received_at,
            'status' => (string) $batch->status,
            'entity' => $batch->entity,
            'processed' => (int) $batch->processed,
            'failed' => (int) $batch->failed,
        ];
    }
}
