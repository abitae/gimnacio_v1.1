<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Models\System\Sucursal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class BioTimeAccessCommandService
{
    public function __construct(
        private readonly BioTimeAccessEligibilityService $eligibility,
        private readonly BioTimeCapacityService $capacity,
    ) {}

    /**
     * Encola activate/deactivate/delete evitando pending identicos (misma sede+cliente+action).
     * emp_code = cliente.numero_documento. Si documento vacio: no encola (null).
     */
    public function enqueue(Sucursal|int $sucursal, Cliente $cliente, string $action): ?BioTimeAccessCommand
    {
        if (! BioTimeAccessCommand::isValidAction($action)) {
            throw new InvalidArgumentException("Accion BioTime no soportada: {$action}");
        }

        $sucursalId = $sucursal instanceof Sucursal ? (int) $sucursal->id : (int) $sucursal;

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if (! $setting->enabled) {
            throw new InvalidArgumentException("BioTime sync deshabilitado para sucursal {$sucursalId}");
        }

        $empCode = BioTimeEmpCode::forCliente($cliente);
        if ($empCode === null) {
            Log::warning('BioTime access: skip enqueue, cliente sin numero_documento', [
                'cliente_id' => $cliente->id,
                'sucursal_id' => $sucursalId,
                'action' => $action,
            ]);

            return null;
        }

        if ($action === BioTimeAccessCommand::ACTION_ACTIVATE) {
            $this->ensureCapacityForActivate($sucursalId, (int) $cliente->id);
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
        $ensureCreate = false;
        $firstName = null;
        $lastName = null;

        if ($action === BioTimeAccessCommand::ACTION_ACTIVATE) {
            $desiredArea = $setting->area_biotime_id;
            $ensureCreate = true;
            $firstName = (string) ($cliente->nombres ?? '');
            $lastName = (string) ($cliente->apellidos ?? '');
        }

        return BioTimeAccessCommand::query()->create([
            'sucursal_id' => $sucursalId,
            'cliente_id' => $cliente->id,
            'emp_code' => $empCode,
            'action' => $action,
            'desired_area_biotime_id' => $desiredArea,
            'ensure_create' => $ensureCreate,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'status' => BioTimeAccessCommand::STATUS_PENDING,
            'attempts' => 0,
        ]);
    }

    /**
     * Compara elegibles vs ultimo estado deseado / empleados conocidos y encola faltantes.
     *
     * @return array{activated:int, deactivated:int, deleted:int, skipped:bool}
     */
    public function reconcileSucursal(int $sucursalId): array
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);

        if (! $setting->enabled) {
            return ['activated' => 0, 'deactivated' => 0, 'deleted' => 0, 'skipped' => true];
        }

        $capacity = $this->capacity->rosterCapacity($sucursalId);
        $roster = $this->capacity->rosterForSucursal($sucursalId);
        $eligibleIds = $roster
            ->whereIn('status', ['selected', 'waiting'])
            ->pluck('cliente_id');
        $preserveEligible = ! $capacity['enforcement_enabled'] || ! $capacity['inventory_ready'];
        $activeIds = $preserveEligible
            ? $eligibleIds
            : $roster->where('desired_access', true)->pluck('cliente_id');
        $selectedSet = array_fill_keys(
            $activeIds->all(),
            true
        );

        $clienteIds = $this->clienteIdsForReconcile($sucursalId, $eligibleIds);
        $activated = 0;
        $deactivated = 0;
        $deleted = 0;

        foreach ($clienteIds as $clienteId) {
            $cliente = Cliente::query()->find($clienteId);
            if (! $cliente instanceof Cliente) {
                continue;
            }

            if (BioTimeEmpCode::forCliente($cliente) === null) {
                continue;
            }

            $shouldBeActive = isset($selectedSet[$clienteId]);
            $lastAction = $this->lastDesiredAction($sucursalId, $clienteId);
            $knownInBioTime = $this->isKnownInBioTime($cliente, $sucursalId);

            if ($shouldBeActive) {
                if ($lastAction !== BioTimeAccessCommand::ACTION_ACTIVATE) {
                    if ($capacity['enforcement_enabled'] && ! $capacity['inventory_ready']) {
                        continue;
                    }

                    try {
                        if ($this->enqueue($sucursalId, $cliente, BioTimeAccessCommand::ACTION_ACTIVATE) !== null) {
                            $activated++;
                        }
                    } catch (InvalidArgumentException $e) {
                        Log::warning('BioTime reconcile activate skipped: '.$e->getMessage(), [
                            'cliente_id' => $clienteId,
                            'sucursal_id' => $sucursalId,
                        ]);
                        BioTimeAccessCommand::query()->create([
                            'sucursal_id' => $sucursalId,
                            'cliente_id' => $cliente->id,
                            'emp_code' => BioTimeEmpCode::forCliente($cliente) ?? '',
                            'action' => BioTimeAccessCommand::ACTION_ACTIVATE,
                            'desired_area_biotime_id' => $setting->area_biotime_id,
                            'ensure_create' => true,
                            'first_name' => (string) ($cliente->nombres ?? ''),
                            'last_name' => (string) ($cliente->apellidos ?? ''),
                            'status' => BioTimeAccessCommand::STATUS_FAILED,
                            'attempts' => 0,
                            'last_error' => $e->getMessage(),
                            'acked_at' => now(),
                        ]);
                    }
                }

                continue;
            }

            // Inactivo: solo desactivar si hubo activate previo o ya existe en BioTime.
            if ($lastAction === BioTimeAccessCommand::ACTION_ACTIVATE || ($lastAction === null && $knownInBioTime)) {
                if ($this->enqueue($sucursalId, $cliente, BioTimeAccessCommand::ACTION_DEACTIVATE) !== null) {
                    $deactivated++;
                }
            }
        }

        return [
            'activated' => $activated,
            'deactivated' => $deactivated,
            'deleted' => $deleted,
            'skipped' => false,
        ];
    }

    /**
     * Compatibilidad: el borrado destructivo automatico queda deshabilitado.
     *
     * @return int cantidad encolada
     */
    public function enqueuePurgeForCapacity(int $sucursalId, int $slotsNeeded = 1): int
    {
        Log::notice('BioTime destructive capacity purge ignored', [
            'sucursal_id' => $sucursalId,
            'slots_needed' => $slotsNeeded,
        ]);

        return 0;
    }

    private function ensureCapacityForActivate(int $sucursalId, int $clienteId): void
    {
        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if (! $setting->capacity_enforcement_enabled) {
            return;
        }

        if ($this->capacity->isClienteSelected($sucursalId, $clienteId)) {
            return;
        }

        throw new InvalidArgumentException(
            'Cliente fuera del roster BioTime seleccionado por capacidad.'
        );
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
            ->where('sucursal_id', $sucursalId)
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
            ->whereIn('action', [
                BioTimeAccessCommand::ACTION_ACTIVATE,
                BioTimeAccessCommand::ACTION_DEACTIVATE,
                BioTimeAccessCommand::ACTION_DELETE,
            ])
            ->latest('id')
            ->first();

        return $command?->action;
    }

    private function isKnownInBioTime(Cliente $cliente, int $sucursalId): bool
    {
        $lookupKeys = BioTimeEmpCode::lookupKeysForCliente($cliente);

        return BioTimeEmployee::query()
            ->where('sucursal_id', $sucursalId)
            ->where(function ($query) use ($cliente, $lookupKeys): void {
                $query->where('cliente_id', $cliente->id);
                if ($lookupKeys !== []) {
                    $query->orWhereIn('emp_code', $lookupKeys);
                }
            })
            ->exists();
    }

    private function supersedeOppositePending(int $sucursalId, int $clienteId, string $action): void
    {
        $opposites = match ($action) {
            BioTimeAccessCommand::ACTION_ACTIVATE => [
                BioTimeAccessCommand::ACTION_DEACTIVATE,
                BioTimeAccessCommand::ACTION_DELETE,
            ],
            BioTimeAccessCommand::ACTION_DEACTIVATE => [
                BioTimeAccessCommand::ACTION_ACTIVATE,
            ],
            BioTimeAccessCommand::ACTION_DELETE => [
                BioTimeAccessCommand::ACTION_ACTIVATE,
                BioTimeAccessCommand::ACTION_DEACTIVATE,
            ],
            default => [],
        };

        if ($opposites === []) {
            return;
        }

        BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $clienteId)
            ->whereIn('action', $opposites)
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
