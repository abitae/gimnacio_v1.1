<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PublicidadResource;
use App\Models\Core\AppPublicidad;
use Illuminate\Http\JsonResponse;

class PublicidadController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => PublicidadResource::collection(
                AppPublicidad::query()->activas()->get()
            ),
        ]);
    }
}
