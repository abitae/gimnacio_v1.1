<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\BioTimeSyncRequest;
use App\Jobs\BioTime\ProcessBioTimeAreas;
use App\Jobs\BioTime\ProcessBioTimeDepartments;
use App\Jobs\BioTime\ProcessBioTimeDevices;
use App\Jobs\BioTime\ProcessBioTimeEmployees;
use App\Jobs\BioTime\ProcessBioTimeTransactions;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeDeviceUser;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Models\Core\Cliente;
use App\Services\BioTime\BioTimeEmpCode;
use App\Services\BioTime\BioTimeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BioTimeSyncController extends Controller
{
    public function __construct(
        private readonly BioTimeSyncService $syncService,
    ) {}

    public function store(BioTimeSyncRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $batchId = (string) Str::uuid();
        $entity = (string) $payload['entity'];
        $records = $payload['data'];
        $receivedAt = now();

        $setting = $this->authenticatedSetting($request);
        $setting->forceFill([
            'last_received_at' => $receivedAt,
            'last_heartbeat_at' => $receivedAt,
        ])->save();

        Cache::put(
            'biotime:last_received_at:'.$setting->sucursal_id,
            $receivedAt->toIso8601String(),
            now()->addMinutes(10)
        );
        // Compat UI global hasta paso 1.3 (dashboard por sede).
        Cache::put('biotime:last_received_at', $receivedAt->toIso8601String(), now()->addMinutes(10));

        BioTimeSyncBatch::query()->create([
            'batch_id' => $batchId,
            'sucursal_id' => $setting->sucursal_id,
            'entity' => $entity,
            'status' => 'pending',
            'received' => count($records),
            'agent_timestamp' => $this->parseDate($payload['timestamp']),
            'received_at' => $receivedAt,
        ]);

        // Catalogo + asistencia: siempre síncrono. En Banahosting sin queue worker
        // el job nunca corre y el mapeo/Checking quedan vacíos.
        $processInline = in_array($entity, ['employees', 'transactions', 'devices', 'areas', 'departments'], true)
            || ! (bool) config('biotime.queue', true);

        if (! $processInline) {
            match ($entity) {
                'transactions' => ProcessBioTimeTransactions::dispatch($payload['timestamp'], $records, $batchId, (int) $setting->sucursal_id),
                'devices' => ProcessBioTimeDevices::dispatch($payload['timestamp'], $records, $batchId, (int) $setting->sucursal_id),
                'areas' => ProcessBioTimeAreas::dispatch($payload['timestamp'], $records, $batchId, (int) $setting->sucursal_id),
                'departments' => ProcessBioTimeDepartments::dispatch($payload['timestamp'], $records, $batchId, (int) $setting->sucursal_id),
                'employees' => ProcessBioTimeEmployees::dispatch($payload['timestamp'], $records, $batchId, (int) $setting->sucursal_id),
            };

            return response()->json([
                'status' => 'accepted',
                'entity' => $entity,
                'received' => count($records),
                'processed' => 0,
                'failed' => 0,
                'queued' => true,
                'batch_id' => $batchId,
                'sucursal_id' => $setting->sucursal_id,
            ], 202);
        }

        $result = $this->syncService->process(
            $entity,
            $payload['timestamp'],
            $records,
            $batchId,
            (int) $setting->sucursal_id
        );

        return response()->json([
            'status' => 'accepted',
            'entity' => $entity,
            'received' => count($records),
            'processed' => $result['processed'],
            'failed' => $result['failed'],
            'queued' => false,
            'batch_id' => $batchId,
            'sucursal_id' => $setting->sucursal_id,
        ]);
    }

    public function health(Request $request): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $heartbeatAt = now();
        $updates = ['last_heartbeat_at' => $heartbeatAt];

        $countRaw = $request->query('employees_count', $request->input('employees_count'));
        if ($countRaw !== null && $countRaw !== '') {
            $updates['employees_count'] = max(0, (int) $countRaw);
        }

        $setting->forceFill($updates)->save();

        return response()->json([
            'status' => 'ok',
            'service' => 'biotime-sync-receiver',
            'sucursal_id' => $setting->sucursal_id,
            'last_heartbeat_at' => $heartbeatAt->toIso8601String(),
            'employees_count' => $setting->employees_count,
            'employee_limit' => (int) ($setting->employee_limit ?: config('biotime.employee_limit_default', 500)),
            'entities' => ['transactions', 'devices', 'areas', 'departments', 'employees'],
            'endpoints' => [
                'GET /api/biotime/health',
                'POST /api/biotime/heartbeat',
                'POST /api/biotime/sync',
                'GET /api/biotime/config',
                'GET /api/biotime/commands',
                'POST /api/biotime/commands/{id}/ack',
                'GET /api/biotime/roster',
            ],
        ]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'bridge_version' => ['nullable', 'string', 'max:50'],
            'devices' => ['required', 'array', 'max:20'],
            'devices.*.biotime_id' => ['nullable', 'integer', 'min:1'],
            'devices.*.serial_number' => ['required', 'string', 'max:100'],
            'devices.*.online' => ['nullable', 'boolean'],
            'devices.*.capacity' => ['nullable', 'integer', 'min:1'],
            'devices.*.employees_count' => ['required', 'integer', 'min:0'],
            'devices.*.employee_codes' => ['present', 'array', 'max:1000'],
            'devices.*.employee_codes.*' => ['string', 'max:50'],
            'devices.*.inventory_at' => ['required', 'date'],
            'devices.*.inventory_source' => ['nullable', 'string', 'max:50'],
        ]);

        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;
        $managedByCode = [];
        Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->get(['id', 'codigo', 'numero_documento'])
            ->each(function (Cliente $cliente) use (&$managedByCode): void {
                foreach (BioTimeEmpCode::lookupKeysForCliente($cliente) as $key) {
                    $managedByCode[$key] = $cliente;
                }
            });

        DB::transaction(function () use ($payload, $sucursalId, $managedByCode): void {
            foreach ($payload['devices'] as $row) {
                $codes = collect($row['employee_codes'])
                    ->map(fn ($code) => trim((string) $code))
                    ->filter()
                    ->unique()
                    ->values();
                $managedCount = $codes
                    ->filter(fn (string $code) => isset($managedByCode[$code]))
                    ->count();
                $protectedCount = max(
                    $codes->count() - $managedCount,
                    (int) $row['employees_count'] - $managedCount
                );

                $device = BioTimeDevice::query()
                    ->where('sucursal_id', $sucursalId)
                    ->where(function ($query) use ($row): void {
                        $query->where('serial_number', (string) $row['serial_number']);

                        if (! empty($row['biotime_id'])) {
                            $query->orWhere('biotime_id', (int) $row['biotime_id']);
                        }
                    })
                    ->firstOrNew();

                $device->fill([
                    'sucursal_id' => $sucursalId,
                    'biotime_id' => $row['biotime_id'] ?? $device->biotime_id,
                    'serial_number' => (string) $row['serial_number'],
                    'state' => ($row['online'] ?? false) ? 1 : 0,
                    'capacity_limit' => min(500, max(1, (int) ($row['capacity'] ?? 500))),
                    'reported_users_count' => (int) $row['employees_count'],
                    'protected_users_count' => max(0, $protectedCount),
                    'inventory_source' => $row['inventory_source'] ?? 'unknown',
                    'inventory_synced_at' => Carbon::parse((string) $row['inventory_at']),
                ])->save();

                BioTimeDeviceUser::query()->where('bio_time_device_id', $device->id)->delete();
                foreach ($codes as $code) {
                    $cliente = $managedByCode[$code] ?? null;
                    BioTimeDeviceUser::query()->create([
                        'bio_time_device_id' => $device->id,
                        'cliente_id' => $cliente?->id,
                        'emp_code' => $code,
                        'managed' => $cliente !== null,
                        'synced_at' => Carbon::parse((string) $row['inventory_at']),
                    ]);
                }
            }
        });

        $setting->forceFill(['last_heartbeat_at' => now()])->save();

        return response()->json([
            'status' => 'ok',
            'sucursal_id' => $sucursalId,
            'devices_received' => count($payload['devices']),
            'config_version' => (int) ($setting->config_version ?: 1),
        ]);
    }

    private function authenticatedSetting(Request $request): BioTimeSucursalSetting
    {
        $setting = $request->attributes->get('biotime_sucursal_setting');

        if (! $setting instanceof BioTimeSucursalSetting) {
            abort(401, 'Unauthorized');
        }

        return $setting;
    }

    private function parseDate(?string $value): ?Carbon
    {
        if (! filled($value)) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
