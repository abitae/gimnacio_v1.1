<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
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
    public function __construct(
        private readonly BioTimeAccessEligibilityService $eligibility,
    ) {}

    public function limitForSucursal(int $sucursalId): int
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        $limit = (int) ($setting->employee_limit ?: config('biotime.employee_limit_default', 500));

        return max(1, $limit);
    }

    public function occupiedForSucursal(int $sucursalId): int
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if ($setting->employees_count !== null) {
            return max(0, (int) $setting->employees_count);
        }

        return $this->estimateOccupiedFromLocalMirror($sucursalId);
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
