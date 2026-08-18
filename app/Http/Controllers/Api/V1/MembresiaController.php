<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MembresiaResource;
use App\Models\Core\ClienteAppAccount;
use App\Services\ClientePortalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MembresiaController extends Controller
{
    public function __construct(protected ClientePortalService $portal) {}

    public function index(Request $request): JsonResponse
    {
        /** @var ClienteAppAccount $account */
        $account = $request->user();

        return response()->json([
            'data' => MembresiaResource::collection(
                $this->portal->membresias((int) $account->cliente_id)
            ),
        ]);
    }
}
