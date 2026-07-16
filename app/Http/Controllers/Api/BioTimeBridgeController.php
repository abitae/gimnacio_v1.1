<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Requests\BioTime\BioTimeCommandAckRequest;
use App\Http\Requests\BioTime\BioTimeCommandsIndexRequest;
use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\Core\Cliente;
use App\Services\BioTime\BioTimeAccessEligibilityService;
use App\Services\BioTime\BioTimeEmpCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BioTimeBridgeController extends Controller
{
    public function __construct(
        private readonly BioTimeAccessEligibilityService $eligibility,
    ) {}

    public function commands(BioTimeCommandsIndexRequest $request): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;
        $limit = (int) ($request->validated('limit') ?? 100);
        $limit = max(1, min($limit, 500));

        $commands = BioTimeAccessCommand::query()
            ->where('sucursal_id', $sucursalId)
            ->where('status', BioTimeAccessCommand::STATUS_PENDING)
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($commands->isNotEmpty()) {
            BioTimeAccessCommand::query()
                ->whereIn('id', $commands->pluck('id'))
                ->where('status', BioTimeAccessCommand::STATUS_PENDING)
                ->update(['status' => BioTimeAccessCommand::STATUS_PROCESSING]);

            $commands->each(fn (BioTimeAccessCommand $cmd) => $cmd->status = BioTimeAccessCommand::STATUS_PROCESSING);
        }

        return response()->json([
            'sucursal_id' => $sucursalId,
            'data' => $commands->map(fn (BioTimeAccessCommand $cmd) => [
                'id' => $cmd->id,
                'emp_code' => $cmd->emp_code,
                'cliente_id' => $cmd->cliente_id,
                'action' => $cmd->action,
                'desired_area_biotime_id' => $cmd->desired_area_biotime_id,
                'status' => $cmd->status,
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
            ])->save();
        } else {
            $command->forceFill([
                'status' => BioTimeAccessCommand::STATUS_FAILED,
                'attempts' => ((int) $command->attempts) + 1,
                'last_error' => isset($payload['error']) ? (string) $payload['error'] : null,
                'acked_at' => $now,
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

    public function roster(Request $request): JsonResponse
    {
        $setting = $this->authenticatedSetting($request);
        $sucursalId = (int) $setting->sucursal_id;

        $eligibleLookup = array_fill_keys(
            $this->eligibility->listEligibleClienteIds($sucursalId)->all(),
            true
        );

        $clientes = Cliente::query()
            ->where('sucursal_id', $sucursalId)
            ->whereNotNull('codigo')
            ->where('codigo', '!=', '')
            ->orderBy('id')
            ->get(['id', 'codigo']);

        return response()->json([
            'sucursal_id' => $sucursalId,
            'data' => $clientes->map(fn (Cliente $cliente) => [
                'cliente_id' => $cliente->id,
                'emp_code' => BioTimeEmpCode::forCliente($cliente),
                'active' => isset($eligibleLookup[$cliente->id]),
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
