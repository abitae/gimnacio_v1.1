<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use Illuminate\Support\Collection;

/**
 * Cupo de empleados BioTime por sede (default 500).
 * Ocupacion = employees_count reportado por el puente, o fallback local.
 */
class BioTimeCapacityService
{
    public const HARD_DEVICE_LIMIT = 500;

    public function __construct(
        private readonly BioTimeAccessEligibilityService $eligibility,
    ) {}

    public function limitForSucursal(int $sucursalId): int
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        $limit = (int) ($setting->employee_limit ?: config('biotime.employee_limit_default', 500));

        return min(self::HARD_DEVICE_LIMIT, max(1, $limit));
    }

    public function occupiedForSucursal(int $sucursalId): int
    {
        $deviceCount = BioTimeDevice::query()
            ->where('sucursal_id', $sucursalId)
            ->where('access_enabled', true)
            ->whereNotNull('reported_users_count')
            ->max('reported_users_count');

        if ($deviceCount !== null) {
            return max(0, (int) $deviceCount);
        }

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if ($setting->employees_count !== null) {
            return max(0, (int) $setting->employees_count);
        }

        return $this->estimateOccupiedFromLocalMirror($sucursalId);
    }

    /**
     * Estado determinista del cupo compartido por todos los relojes de una sede.
     *
     * @return array{
     *   hard_limit:int,client_slots:int,enforcement_enabled:bool,inventory_ready:bool,
     *   reason:?string,selected_count:int,waiting_count:int
     * }
     */
    public function rosterCapacity(int $sucursalId): array
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        $eligibleCount = $this->eligibility->listEligibleClienteIds($sucursalId)->count();
        $enforcement = (bool) $setting->capacity_enforcement_enabled;
        $limit = $this->limitForSucursal($sucursalId);

        if (! $enforcement) {
            return [
                'hard_limit' => $limit,
                'client_slots' => $limit,
                'enforcement_enabled' => false,
                'inventory_ready' => false,
                'reason' => 'observation_mode',
                'selected_count' => min($eligibleCount, $limit),
                'waiting_count' => max(0, $eligibleCount - $limit),
            ];
        }

        $devices = BioTimeDevice::query()
            ->where('sucursal_id', $sucursalId)
            ->where('access_enabled', true)
            ->get();

        if ($devices->isEmpty()) {
            return $this->blockedCapacity($limit, $eligibleCount, 'no_access_devices');
        }

        $freshAfter = now()->subSeconds(max(120, ((int) $setting->poll_interval_seconds) * 2));
        $inventoryReady = $devices->every(fn (BioTimeDevice $device): bool => $device->inventory_verified
            && in_array($device->state, [1, 2], true)
            && in_array($device->inventory_source, ['terminal_counter', 'terminal_inventory'], true)
            && $device->inventory_synced_at !== null
            && $device->inventory_synced_at->gte($freshAfter)
            && $device->reported_users_count !== null
        );

        if (! $inventoryReady) {
            return $this->blockedCapacity($limit, $eligibleCount, 'inventory_stale_or_unverified');
        }

        $clientSlots = (int) $devices
            ->map(function (BioTimeDevice $device) use ($limit): int {
                $deviceLimit = min(
                    self::HARD_DEVICE_LIMIT,
                    $limit,
                    max(1, (int) ($device->capacity_limit ?: self::HARD_DEVICE_LIMIT))
                );

                return max(0, $deviceLimit - max(0, (int) $device->protected_users_count));
            })
            ->min();

        return [
            'hard_limit' => $limit,
            'client_slots' => $clientSlots,
            'enforcement_enabled' => true,
            'inventory_ready' => true,
            'reason' => null,
            'selected_count' => min($eligibleCount, $clientSlots),
            'waiting_count' => max(0, $eligibleCount - $clientSlots),
        ];
    }

    /**
     * @return Collection<int, array{
     *   cliente_id:int,emp_code:string,desired_access:bool,priority_at:?string,
     *   rank:?int,status:string
     * }>
     */
    public function rosterForSucursal(int $sucursalId): Collection
    {
        $capacity = $this->rosterCapacity($sucursalId);
        $priorityRows = $this->eligibility->prioritizedEligible($sucursalId);
        $priorityByCliente = $priorityRows->keyBy('cliente_id');
        $selectedIds = array_fill_keys(
            $priorityRows->take($capacity['client_slots'])->pluck('cliente_id')->all(),
            true
        );

        return Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->whereNotNull('codigo')
            ->where('codigo', '!=', '')
            ->orderBy('id')
            ->get(['id', 'codigo'])
            ->map(function (Cliente $cliente) use ($priorityByCliente, $selectedIds): array {
                $priority = $priorityByCliente->get((int) $cliente->id);
                $eligible = is_array($priority);
                $selected = isset($selectedIds[(int) $cliente->id]);

                return [
                    'cliente_id' => (int) $cliente->id,
                    'emp_code' => (string) $cliente->codigo,
                    'desired_access' => $selected,
                    'priority_at' => $priority['priority_at'] ?? null,
                    'rank' => $priority['rank'] ?? null,
                    'status' => $selected ? 'selected' : ($eligible ? 'waiting' : 'denied'),
                ];
            })
            ->values();
    }

    public function isClienteSelected(int $sucursalId, int $clienteId): bool
    {
        return $this->rosterForSucursal($sucursalId)
            ->contains(fn (array $row): bool => $row['cliente_id'] === $clienteId && $row['desired_access'] === true
            );
    }

    public function remainingSlots(int $sucursalId): int
    {
        return max(0, $this->limitForSucursal($sucursalId) - $this->occupiedForSucursal($sucursalId));
    }

    public function isAtOrOverLimit(int $sucursalId): bool
    {
        return $this->occupiedForSucursal($sucursalId) >= $this->limitForSucursal($sucursalId);
    }

    public function usagePercent(int $sucursalId): float
    {
        $limit = $this->limitForSucursal($sucursalId);

        return round(($this->occupiedForSucursal($sucursalId) / $limit) * 100, 1);
    }

    public function isAlertThreshold(int $sucursalId): bool
    {
        $threshold = (int) config('biotime.employee_limit_alert_percent', 90);

        return $this->usagePercent($sucursalId) >= $threshold;
    }

    /**
     * Clientes inelegibles de la sede que ya existen en el espejo BioTime,
     * ordenados por deactivate mas antiguo primero (candidatos a borrado destructivo).
     *
     * @return Collection<int, Cliente>
     */
    public function purgeCandidates(int $sucursalId, int $limit = 50): Collection
    {
        $eligibleSet = array_fill_keys(
            $this->eligibility->listEligibleClienteIds($sucursalId)->all(),
            true
        );

        $clientes = Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->whereNotNull('codigo')
            ->where('codigo', '!=', '')
            ->orderBy('id')
            ->get();

        $candidates = $clientes->filter(function (Cliente $cliente) use ($eligibleSet, $sucursalId): bool {
            if (isset($eligibleSet[$cliente->id])) {
                return false;
            }

            return $this->isKnownClientEmployee($cliente, $sucursalId);
        })->values();

        return $candidates
            ->sortBy(fn (Cliente $cliente) => $this->lastDeactivateAckedAt($sucursalId, (int) $cliente->id) ?? '9999-12-31')
            ->take(max(1, $limit))
            ->values();
    }

    public function recordEmployeesCount(int $sucursalId, int $count): void
    {
        BioTimeSucursalSetting::forSucursal($sucursalId)->forceFill([
            'employees_count' => max(0, $count),
        ])->save();
    }

    /** @return array{hard_limit:int,client_slots:int,enforcement_enabled:bool,inventory_ready:bool,reason:string,selected_count:int,waiting_count:int} */
    private function blockedCapacity(int $limit, int $eligibleCount, string $reason): array
    {
        return [
            'hard_limit' => $limit,
            'client_slots' => 0,
            'enforcement_enabled' => true,
            'inventory_ready' => false,
            'reason' => $reason,
            'selected_count' => 0,
            'waiting_count' => $eligibleCount,
        ];
    }

    private function estimateOccupiedFromLocalMirror(int $sucursalId): int
    {
        $codigos = Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->whereNotNull('codigo')
            ->where('codigo', '!=', '')
            ->pluck('codigo')
            ->map(fn ($c) => (string) $c)
            ->all();

        $clienteIds = Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->pluck('id')
            ->all();

        return (int) BioTimeEmployee::query()
            ->where('sucursal_id', $sucursalId)
            ->where(function ($query) use ($clienteIds, $codigos): void {
                if ($clienteIds !== []) {
                    $query->whereIn('cliente_id', $clienteIds);
                }
                if ($codigos !== []) {
                    $query->orWhereIn('emp_code', $codigos);
                }
            })
            ->count();
    }

    private function isKnownClientEmployee(Cliente $cliente, int $sucursalId): bool
    {
        $empCode = BioTimeEmpCode::forCliente($cliente);
        if ($empCode === null) {
            return false;
        }

        $inMirror = BioTimeEmployee::query()
            ->where('sucursal_id', $sucursalId)
            ->where(function ($query) use ($cliente, $empCode): void {
                $query->where('cliente_id', $cliente->id)
                    ->orWhere('emp_code', $empCode);
            })
            ->exists();

        if ($inMirror) {
            return true;
        }

        return BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $cliente->id)
            ->where('action', BioTimeAccessCommand::ACTION_ACTIVATE)
            ->where('status', BioTimeAccessCommand::STATUS_ACKED)
            ->exists();
    }

    private function lastDeactivateAckedAt(int $sucursalId, int $clienteId): ?string
    {
        $command = BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $clienteId)
            ->where('action', BioTimeAccessCommand::ACTION_DEACTIVATE)
            ->where('status', BioTimeAccessCommand::STATUS_ACKED)
            ->latest('acked_at')
            ->first();

        return $command?->acked_at?->toDateTimeString();
    }
}
