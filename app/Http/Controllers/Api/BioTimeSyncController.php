<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\BioTimeSyncRequest;
use App\Jobs\BioTime\ProcessBioTimeAreas;
use App\Jobs\BioTime\ProcessBioTimeDepartments;
use App\Jobs\BioTime\ProcessBioTimeDevices;
use App\Jobs\BioTime\ProcessBioTimeEmployees;
use App\Jobs\BioTime\ProcessBioTimeTransactions;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Services\BioTime\BioTimeSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
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

        if ((bool) config('biotime.queue', true)) {
            match ($entity) {
                'transactions' => ProcessBioTimeTransactions::dispatch($payload['timestamp'], $records, $batchId),
                'devices' => ProcessBioTimeDevices::dispatch($payload['timestamp'], $records, $batchId),
                'areas' => ProcessBioTimeAreas::dispatch($payload['timestamp'], $records, $batchId),
                'departments' => ProcessBioTimeDepartments::dispatch($payload['timestamp'], $records, $batchId),
                'employees' => ProcessBioTimeEmployees::dispatch($payload['timestamp'], $records, $batchId),
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

        $result = $this->syncService->process($entity, $payload['timestamp'], $records, $batchId);

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
                'POST /api/biotime/sync',
                'GET /api/biotime/commands',
                'POST /api/biotime/commands/{id}/ack',
                'GET /api/biotime/roster',
            ],
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
