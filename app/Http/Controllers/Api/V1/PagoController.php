<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PagoPendienteResource;
use App\Http\Resources\Api\V1\PagoResource;
use App\Models\Core\ClienteAppAccount;
use App\Services\ClientePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PagoController extends Controller
{
    public function __construct(protected ClientePortalService $portal) {}

    public function pendientes(Request $request): JsonResponse
    {
        /** @var ClienteAppAccount $account */
        $account = $request->user();
        $summary = $this->portal->pagosPendientes((int) $account->cliente_id);

        return response()->json([
            'total_pendiente' => $summary['total_pendiente'],
            'cantidad_items' => $summary['cantidad_items'],
            'tiene_deuda' => $summary['tiene_deuda'],
            'tiene_deuda_vencida' => $summary['tiene_deuda_vencida'],
            'data' => PagoPendienteResource::collection($summary['items']),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var ClienteAppAccount $account */
        $account = $request->user();

        return PagoResource::collection(
            $this->portal->pagos((int) $account->cliente_id)
        );
    }
}
