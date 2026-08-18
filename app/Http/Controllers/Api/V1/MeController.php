<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClienteMeResource;
use App\Models\Core\ClienteAppAccount;
use App\Services\ClientePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __construct(protected ClientePortalService $portal) {}

    public function show(Request $request): JsonResponse
    {
        /** @var ClienteAppAccount $account */
        $account = $request->user();

        return response()->json([
            'data' => new ClienteMeResource($this->portal->cliente((int) $account->cliente_id)),
        ]);
    }
}
