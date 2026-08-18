<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ActivarClienteAppRequest;
use App\Http\Requests\Api\V1\CambiarPasswordClienteAppRequest;
use App\Http\Requests\Api\V1\LoginClienteAppRequest;
use App\Http\Resources\Api\V1\ClienteMeResource;
use App\Models\Core\ClienteAppAccount;
use App\Services\ClienteAppAuthService;
use App\Support\ClientePortalQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(protected ClienteAppAuthService $authService) {}

    public function activar(ActivarClienteAppRequest $request): JsonResponse
    {
        $result = $this->authService->activar(
            (string) $request->validated('tipo_documento'),
            (string) $request->validated('numero_documento'),
            (string) $request->validated('password'),
        );

        return $this->tokenResponse($result, 201);
    }

    public function login(LoginClienteAppRequest $request): JsonResponse
    {
        $result = $this->authService->login(
            (string) $request->validated('tipo_documento'),
            (string) $request->validated('numero_documento'),
            (string) $request->validated('password'),
        );

        return $this->tokenResponse($result);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var ClienteAppAccount $account */
        $account = $request->user();
        $this->authService->logout($account);

        return response()->json(['message' => 'Sesión cerrada.']);
    }

    public function cambiarPassword(CambiarPasswordClienteAppRequest $request): JsonResponse
    {
        /** @var ClienteAppAccount $account */
        $account = $request->user();
        $this->authService->cambiarPassword(
            $account,
            (string) $request->validated('password_actual'),
            (string) $request->validated('password'),
        );

        return response()->json(['message' => 'Contraseña actualizada. Vuelve a iniciar sesión.']);
    }

    /**
     * @param  array{token: string, account: ClienteAppAccount, cliente: \App\Models\Core\Cliente}  $result
     */
    protected function tokenResponse(array $result, int $status = 200): JsonResponse
    {
        $cliente = ClientePortalQuery::clientes()
            ->with('sucursal')
            ->findOrFail($result['cliente']->id);

        return response()->json([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'cliente' => (new ClienteMeResource($cliente))->resolve(),
        ], $status);
    }
}
