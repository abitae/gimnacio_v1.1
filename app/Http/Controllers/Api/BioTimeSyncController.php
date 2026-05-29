<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\BioTimeSyncRequest;
use App\Jobs\BioTime\ProcessBioTimeAreas;
use App\Jobs\BioTime\ProcessBioTimeDepartments;
use App\Jobs\BioTime\ProcessBioTimeDevices;
use App\Jobs\BioTime\ProcessBioTimeEmployees;
use App\Jobs\BioTime\ProcessBioTimeTransactions;
use App\Models\BioTime\BioTimeSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Services\BioTime\BioTimeSyncService;
use Illuminate\Http\JsonResponse;
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

        BioTimeSetting::current()->forceFill(['last_received_at' => $receivedAt])->save();
        Cache::put('biotime:last_received_at', $receivedAt->toIso8601String(), now()->addMinutes(10));

        BioTimeSyncBatch::query()->create([
            'batch_id' => $batchId,
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
        ]);
    }

    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => 'biotime-sync-receiver',
            'entities' => ['transactions', 'devices', 'areas', 'departments', 'employees'],
        ]);
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
