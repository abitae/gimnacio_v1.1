<?php

namespace App\Http\Middleware;

use App\Models\Core\ClienteAppAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClienteAppAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() instanceof ClienteAppAccount) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        return $next($request);
    }
}
