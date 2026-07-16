<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Models\System\Sucursal;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class BioTimeAccessCommandService
{
    public function __construct(
        private readonly BioTimeAccessEligibilityService $eligibility,
    ) {}

    /**
     * Encola activate/deactivate evitando pending identicos (misma sede+cliente+action).
     */
    public function enqueue(Sucursal|int $sucursal, Cliente $cliente, string $action): BioTimeAccessCommand
    {
        if (! BioTimeAccessCommand::isValidAction($action)) {
            throw new InvalidArgumentException("Accion BioTime no soportada: {$action}");
        }

        $sucursalId = $sucursal instanceof Sucursal ? (int) $sucursal->id : (int) $sucursal;

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if (! $setting->enabled) {
            throw new InvalidArgumentException("BioTime sync deshabilitado para sucursal {$sucursalId}");
        }

        $existing = BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $cliente->id)
            ->where('action', $action)
            ->where('status', BioTimeAccessCommand::STATUS_PENDING)
            ->latest('id')
            ->first();

        if ($existing instanceof BioTimeAccessCommand) {
            return $existing;
        }

        $this->supersedeOppositePending($sucursalId, (int) $cliente->id, $action);

        $desiredArea = null;
        if ($action === BioTimeAccessCommand::ACTION_ACTIVATE) {
            $desiredArea = $setting->area_biotime_id;
        }

        return BioTimeAccessCommand::query()->create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $cliente->id,
            'emp_code' => (string) $cliente->id,
            'action' => $action,
            'desired_area_biotime_id' => $desiredArea,
            'status' => BioTimeAccessCommand::STATUS_PENDING,
            'attempts' => 0,
        ]);
    }

    /**
     * Compara elegibles vs ultimo estado deseado / empleados conocidos y encola faltantes.
     *
     * @return array{activated:int, deactivated:int, skipped:bool}
     */
    public function reconcileSucursal(int $sucursalId): array
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);

        if (! $setting->enabled) {
            return ['activated' => 0, 'deactivated' => 0, 'skipped' => true];
        }

        $eligibleIds = $this->eligibility->listEligibleClienteIds($sucursalId);
        $eligibleSet = array_fill_keys($eligibleIds->all(), true);

        $clienteIds = $this->clienteIdsForReconcile($sucursalId, $eligibleIds);
        $activated = 0;
        $deactivated = 0;

        foreach ($clienteIds as $clienteId) {
            $cliente = Cliente::query()->find($clienteId);
            if (! $cliente instanceof Cliente) {
                continue;
            }

            $shouldBeActive = isset($eligibleSet[$clienteId]);
            $lastAction = $this->lastDesiredAction($sucursalId, $clienteId);
            $knownInBioTime = $this->isKnownInBioTime($clienteId);

            if ($shouldBeActive) {
                if ($lastAction !== BioTimeAccessCommand::ACTION_ACTIVATE) {
                    $this->enqueue($sucursalId, $cliente, BioTimeAccessCommand::ACTION_ACTIVATE);
                    $activated++;
                }

                continue;
            }

            // Inactivo: solo desactivar si hubo activate previo o ya existe en BioTime.
            if ($lastAction === BioTimeAccessCommand::ACTION_ACTIVATE || ($lastAction === null && $knownInBioTime)) {
                $this->enqueue($sucursalId, $cliente, BioTimeAccessCommand::ACTION_DEACTIVATE);
                $deactivated++;
            }
        }

        return ['activated' => $activated, 'deactivated' => $deactivated, 'skipped' => false];
    }

    /**
     * @param  Collection<int, int>  $eligibleIds
     * @return list<int>
     */
    private function clienteIdsForReconcile(int $sucursalId, Collection $eligibleIds): array
    {
        $fromSucursal = Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->pluck('id')
            ->map(fn ($id) => (int) $id);

        $fromEmployees = BioTimeEmployee::query()
            ->whereNotNull('cliente_id')
            ->whereIn('cliente_id', $fromSucursal)
            ->pluck('cliente_id')
            ->map(fn ($id) => (int) $id);

        $fromCommands = BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->pluck('cliente_id')
            ->map(fn ($id) => (int) $id);

        return $fromSucursal
            ->merge($eligibleIds)
            ->merge($fromEmployees)
            ->merge($fromCommands)
            ->unique()
            ->values()
            ->all();
    }

    private function lastDesiredAction(int $sucursalId, int $clienteId): ?string
    {
        $command = BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $clienteId)
            ->whereIn('status', [
                BioTimeAccessCommand::STATUS_PENDING,
                BioTimeAccessCommand::STATUS_PROCESSING,
                BioTimeAccessCommand::STATUS_ACKED,
            ])
            ->latest('id')
            ->first();

        return $command?->action;
    }

    private function isKnownInBioTime(int $clienteId): bool
    {
        return BioTimeEmployee::query()
            ->where(function ($query) use ($clienteId): void {
                $query->where('cliente_id', $clienteId)
                    ->orWhere('emp_code', (string) $clienteId);
            })
            ->exists();
    }

    private function supersedeOppositePending(int $sucursalId, int $clienteId, string $action): void
    {
        $opposite = $action === BioTimeAccessCommand::ACTION_ACTIVATE
            ? BioTimeAccessCommand::ACTION_DEACTIVATE
            : BioTimeAccessCommand::ACTION_ACTIVATE;

        BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $clienteId)
            ->where('action', $opposite)
            ->whereIn('status', [
                BioTimeAccessCommand::STATUS_PENDING,
                BioTimeAccessCommand::STATUS_PROCESSING,
            ])
            ->update([
                'status' => BioTimeAccessCommand::STATUS_FAILED,
                'last_error' => 'Superseded by '.$action,
                'acked_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
