<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\BioTime\BioTimeCommandAckRequest;
use App\Http\Requests\BioTime\BioTimeCommandsIndexRequest;
use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Services\BioTime\BioTimeCapacityService;
use App\Services\BioTime\BioTimeEmpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;

class BioTimeBridgeController extends Controller
{
    public function __construct(
        private readonly BioTimeCapacityService $capacity,
    ) {}

    public function config(Request $request): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;
        $capacity = $this->capacity->rosterCapacity($sucursalId);

        return response()->json([
            'sucursal_id' => $sucursalId,
            'config_version' => (int) ($setting->config_version ?: 1),
            'enabled' => (bool) $setting->enabled,
            'capacity_enforcement_enabled' => (bool) $setting->capacity_enforcement_enabled,
            'hard_limit' => $capacity['hard_limit'],
            'absolute_limit' => BioTimeCapacityService::HARD_DEVICE_LIMIT,
            'capacity' => $capacity,
            'area_biotime_id' => $setting->area_biotime_id,
            'denied_area_biotime_id' => $setting->denied_area_biotime_id,
            'company_biotime_id' => $setting->company_biotime_id,
            'department_biotime_id' => $setting->department_biotime_id,
            'poll_interval_seconds' => (int) $setting->poll_interval_seconds,
            'devices' => BioTimeDevice::query()
                ->where('sucursal_id', $sucursalId)
                ->orderBy('alias')
                ->get()
                ->map(fn ($device) => [
                    'biotime_id' => $device->biotime_id,
                    'serial_number' => $device->serial_number,
                    'alias' => $device->alias,
                    'access_enabled' => (bool) $device->access_enabled,
                    'access_role' => $device->access_role,
                    'capacity_limit' => min(
                        BioTimeCapacityService::HARD_DEVICE_LIMIT,
                        max(1, (int) $device->capacity_limit)
                    ),
                    'reported_users_count' => $device->reported_users_count,
                    'protected_users_count' => (int) $device->protected_users_count,
                    'inventory_verified' => (bool) $device->inventory_verified,
                    'inventory_source' => $device->inventory_source,
                    'inventory_synced_at' => $device->inventory_synced_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    public function commands(BioTimeCommandsIndexRequest $request): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;
        $limit = (int) ($request->validated('limit') ?? 100);
        $limit = max(1, min($limit, 500));

        $commands = DB::transaction(function () use ($sucursalId, $limit) {
            $commands = BioTimeAccessCommand::query()
                ->with('cliente')
                ->where('sucursal_id', $sucursalId)
                ->where(function ($query): void {
                    $query->where('status', BioTimeAccessCommand::STATUS_PENDING)
                        ->orWhere(function ($expired): void {
                            $expired->where('status', BioTimeAccessCommand::STATUS_PROCESSING)
                                ->where(function ($lease): void {
                                    $lease->whereNull('lease_expires_at')
                                        ->orWhere('lease_expires_at', '<=', now());
                                });
                        });
                })
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            $leaseExpiresAt = now()->addMinutes(5);
            foreach ($commands as $command) {
                $canonical = $command->cliente instanceof Cliente
                    ? BioTimeEmpCode::forCliente($command->cliente)
                    : null;
                $fill = [
                    'status' => BioTimeAccessCommand::STATUS_PROCESSING,
                    'leased_at' => now(),
                    'lease_expires_at' => $leaseExpiresAt,
                    'attempts' => (int) $command->attempts + 1,
                ];
                if ($canonical !== null && $canonical !== (string) $command->emp_code) {
                    $fill['emp_code'] = $canonical;
                }
                $command->forceFill($fill)->save();
            }

            return $commands;
        });

        return response()->json([
            'sucursal_id' => $sucursalId,
            'data' => $commands->map(fn (BioTimeAccessCommand $cmd) => [
                'id' => $cmd->id,
                'idempotency_key' => $cmd->idempotency_key,
                'emp_code' => $cmd->emp_code,
                'emp_code_aliases' => [],
                'cliente_id' => $cmd->cliente_id,
                'action' => $cmd->action,
                'desired_area_biotime_id' => $cmd->desired_area_biotime_id,
                'ensure_create' => (bool) $cmd->ensure_create,
                'first_name' => $cmd->first_name,
                'last_name' => $cmd->last_name,
                'status' => $cmd->status,
                'lease_expires_at' => $cmd->lease_expires_at?->toIso8601String(),
            ])->values(),
        ]);
    }

    public function ack(BioTimeCommandAckRequest $request, int $id): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;
        $payload = $request->validated();
        $ackStatus = (string) $payload['status'];

        $command = BioTimeAccessCommand::query()
            ->whereKey($id)
            ->where('sucursal_id', $sucursalId)
            ->first();

        if (! $command instanceof BioTimeAccessCommand) {
            return response()->json(['message' => 'Command not found for this sucursal'], 404);
        }

        $now = now();

        if ($ackStatus === BioTimeAccessCommand::STATUS_ACKED) {
            $command->forceFill([
                'status' => BioTimeAccessCommand::STATUS_ACKED,
                'last_error' => null,
                'acked_at' => $now,
                'lease_expires_at' => null,
            ])->save();

            if ($command->action === BioTimeAccessCommand::ACTION_DELETE) {
                $this->forgetEmployeeAfterDelete($command);
            } else {
                $biotimeId = isset($payload['biotime_id']) ? (int) $payload['biotime_id'] : null;
                $this->rememberEmployeeFromAck($command, $biotimeId > 0 ? $biotimeId : null);
            }
        } else {
            $command->forceFill([
                'status' => BioTimeAccessCommand::STATUS_FAILED,
                'attempts' => max(1, (int) $command->attempts),
                'last_error' => isset($payload['error']) ? (string) $payload['error'] : null,
                'acked_at' => $now,
                'lease_expires_at' => null,
            ])->save();
        }

        $setting->forceFill(['last_heartbeat_at' => $now])->save();

        return response()->json([
            'status' => 'ok',
            'command' => [
                'id' => $command->id,
                'status' => $command->status,
                'attempts' => $command->attempts,
                'acked_at' => $command->acked_at?->toIso8601String(),
            ],
            'sucursal_id' => $sucursalId,
        ]);
    }

    /**
     * Enlaza cliente ↔ empleado BioTime tras activate/deactivate ACK (sin esperar sync).
     */
    private function rememberEmployeeFromAck(BioTimeAccessCommand $command, ?int $biotimeId): void
    {
        $empCode = filled($command->emp_code) ? (string) $command->emp_code : null;
        if ($empCode === null && $biotimeId === null) {
            return;
        }

        $lookup = $biotimeId !== null
            ? ['biotime_id' => $biotimeId]
            : ['emp_code' => $empCode];

        BioTimeEmployee::query()->updateOrCreate(
            ['sucursal_id' => (int) $command->sucursal_id] + $lookup,
            [
                'sucursal_id' => (int) $command->sucursal_id,
                'biotime_id' => $biotimeId,
                'emp_code' => $empCode,
                'cliente_id' => $command->cliente_id,
                'first_name' => $command->first_name,
                'last_name' => $command->last_name,
                'area_biotime_ids' => $command->desired_area_biotime_id !== null
                    ? [(int) $command->desired_area_biotime_id]
                    : null,
                'synced_at' => now(),
            ]
        );

        if ($biotimeId !== null) {
            Cliente::query()
                ->whereKey($command->cliente_id)
                ->where(function ($q) use ($biotimeId): void {
                    $q->whereNull('biotime_id')
                        ->orWhere('biotime_id', '!=', $biotimeId);
                })
                ->update(['biotime_id' => $biotimeId]);
        }
    }

    private function forgetEmployeeAfterDelete(BioTimeAccessCommand $command): void
    {
        BioTimeEmployee::query()
            ->where('sucursal_id', (int) $command->sucursal_id)
            ->where(function ($query) use ($command): void {
                $command->loadMissing('cliente');
                $query->where('cliente_id', $command->cliente_id);
                $keys = $command->cliente instanceof Cliente
                    ? BioTimeEmpCode::lookupKeysForCliente($command->cliente)
                    : (filled($command->emp_code) ? [(string) $command->emp_code] : []);
                if ($keys !== []) {
                    $query->orWhereIn('emp_code', $keys);
                }
            })
            ->delete();

        Cliente::query()
            ->whereKey($command->cliente_id)
            ->update(['biotime_id' => null]);
    }

    public function roster(Request $request): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;

        $capacity = $this->capacity->rosterCapacity($sucursalId);
        $roster = $this->capacity->rosterForSucursal($sucursalId);

        return response()->json([
            'sucursal_id' => $sucursalId,
            'generated_at' => now()->toIso8601String(),
            'capacity' => $capacity,
            'data' => $roster->map(fn (array $row) => $row + [
                // Alias temporal para puentes anteriores.
                'active' => $capacity['enforcement_enabled']
                    ? $row['desired_access']
                    : $row['status'] !== 'denied',
            ])->values(),
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
}
