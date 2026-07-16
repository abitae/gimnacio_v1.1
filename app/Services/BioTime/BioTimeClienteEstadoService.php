<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Services\ClienteService;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * Estado Laravel del cliente + snapshot de acceso BioTime para UI de perfil.
 */
class BioTimeClienteEstadoService
{
    public function __construct(
        private readonly BioTimeAccessEligibilityService $eligibility,
        private readonly ClienteService $clienteService,
        private readonly BioTimeAccessCommandService $commands,
    ) {}

    /**
     * Tiene suscripcion vigente (membresia o clase) en su sucursal.
     */
    public function hasActiveSubscription(Cliente $cliente): bool
    {
        $sucursalId = (int) $cliente->sucursal_id;
        if ($sucursalId <= 0) {
            return false;
        }

        return $this->eligibility->isEligible($cliente, $sucursalId);
    }

    /**
     * @return array{status:string, label:string, can_activate:bool, can_deactivate:bool}
     */
    public function profileBioTimeSnapshot(Cliente $cliente): array
    {
        $sucursalId = (int) $cliente->sucursal_id;
        $empCode = BioTimeEmpCode::forCliente($cliente);

        $pending = BioTimeAccessCommand::query()
            ->where('cliente_id', $cliente->id)
            ->when($sucursalId > 0, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->whereIn('status', [
                BioTimeAccessCommand::STATUS_PENDING,
                BioTimeAccessCommand::STATUS_PROCESSING,
            ])
            ->latest('id')
            ->first();

        if ($pending instanceof BioTimeAccessCommand) {
            return [
                'status' => 'pendiente',
                'label' => 'Pendiente ('.$pending->action.')',
                'can_activate' => false,
                'can_deactivate' => $cliente->estado_cliente === 'activo',
            ];
        }

        $known = $this->isKnownInBioTime($cliente);

        if (! $known && $cliente->biotime_id === null) {
            $status = 'no_existe';
            $label = 'No existe en BioTime';
        } elseif ($sucursalId > 0 && $this->eligibility->isEligible($cliente, $sucursalId)) {
            $last = $this->lastAckedAction($cliente, $sucursalId);
            if ($last === BioTimeAccessCommand::ACTION_DEACTIVATE) {
                $status = 'inactivo';
                $label = 'Inactivo en BioTime';
            } else {
                $status = 'activo';
                $label = 'Activo en BioTime';
            }
        } else {
            $status = $known ? 'inactivo' : 'no_existe';
            $label = $known ? 'Inactivo en BioTime' : 'No existe en BioTime';
        }

        return [
            'status' => $status,
            'label' => $label,
            'can_activate' => $cliente->estado_cliente !== 'activo',
            'can_deactivate' => $cliente->estado_cliente === 'activo',
        ];
    }

    /**
     * @return array{cliente: Cliente, biotime_command: ?BioTimeAccessCommand}
     */
    public function activateEstadoCliente(Cliente $cliente): array
    {
        if (! $this->hasActiveSubscription($cliente)) {
            throw new InvalidArgumentException(
                'No se puede activar: el cliente no tiene membresía o clase vigente.'
            );
        }

        $updated = $this->clienteService->update((int) $cliente->id, [
            'estado_cliente' => 'activo',
        ]);

        $command = $this->tryEnqueue($updated, BioTimeAccessCommand::ACTION_ACTIVATE);

        return [
            'cliente' => $updated,
            'biotime_command' => $command,
        ];
    }

    /**
     * @return array{cliente: Cliente, biotime_command: ?BioTimeAccessCommand}
     */
    public function deactivateEstadoCliente(Cliente $cliente): array
    {
        $updated = $this->clienteService->update((int) $cliente->id, [
            'estado_cliente' => 'inactivo',
        ]);

        // Siempre encolar si hay codigo + sede habilitada. El bridge hace no-op si
        // el empleado no existe en BioTime. No depender de BioTimeEmployee local
        // (sync a menudo queda en queue sin worker en shared hosting).
        $command = $this->tryEnqueue($updated, BioTimeAccessCommand::ACTION_DEACTIVATE);

        return [
            'cliente' => $updated,
            'biotime_command' => $command,
        ];
    }

    private function tryEnqueue(Cliente $cliente, string $action): ?BioTimeAccessCommand
    {
        $sucursalId = (int) $cliente->sucursal_id;
        if ($sucursalId <= 0) {
            return null;
        }

        if (BioTimeEmpCode::forCliente($cliente) === null) {
            Log::warning('BioTime perfil: skip enqueue, cliente sin codigo', [
                'cliente_id' => $cliente->id,
                'action' => $action,
            ]);

            return null;
        }

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if (! $setting->enabled) {
            Log::warning('BioTime perfil: skip enqueue, sede deshabilitada', [
                'cliente_id' => $cliente->id,
                'sucursal_id' => $sucursalId,
                'action' => $action,
            ]);

            return null;
        }

        try {
            return $this->commands->enqueue($sucursalId, $cliente, $action);
        } catch (InvalidArgumentException $e) {
            Log::warning('BioTime perfil: enqueue falló', [
                'cliente_id' => $cliente->id,
                'sucursal_id' => $sucursalId,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function isKnownInBioTime(Cliente $cliente): bool
    {
        if ($cliente->biotime_id !== null) {
            return true;
        }

        $empCode = BioTimeEmpCode::forCliente($cliente);
        if ($empCode === null) {
            return false;
        }

        return BioTimeEmployee::query()
            ->where(function ($query) use ($cliente, $empCode): void {
                $query->where('cliente_id', $cliente->id)
                    ->orWhere('emp_code', $empCode);
            })
            ->exists();
    }

    private function lastAckedAction(Cliente $cliente, int $sucursalId): ?string
    {
        $command = BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('cliente_id', $cliente->id)
            ->where('status', BioTimeAccessCommand::STATUS_ACKED)
            ->latest('id')
            ->first();

        return $command?->action;
    }
}
