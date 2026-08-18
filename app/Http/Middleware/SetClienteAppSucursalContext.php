<?php

namespace App\Http\Middleware;

use App\Models\Core\ClienteAppAccount;
use App\Services\SucursalContext;
use App\Support\ClientePortalQuery;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetClienteAppSucursalContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = app(SucursalContext::class);
        $account = $request->user();

        if ($account instanceof ClienteAppAccount) {
            $cliente = ClientePortalQuery::clientes()->find($account->cliente_id);
            $sucursalId = $cliente?->sucursal_id;

            if ($sucursalId) {
                $context->setDelegateContext((int) $sucursalId);
            }
        }

        try {
            return $next($request);
        } finally {
            $context->clearDelegateContext();
        }
    }
}
