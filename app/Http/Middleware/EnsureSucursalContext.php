<?php

namespace App\Http\Middleware;

use App\Services\SucursalContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSucursalContext
{
    public function __construct(
        protected SucursalContext $context
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        $available = $this->context->availableForUser($user);

        if ($available->isEmpty()) {
            $this->context->clear();
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu usuario no tiene sucursales activas asignadas.',
            ]);
        }

        $activeSucursal = $this->context->resolveForUser($user, $this->context->getSucursalId());

        if ($activeSucursal) {
            $this->context->activate($activeSucursal);

            return $next($request);
        }

        if ($available->count() === 1) {
            $this->context->activate($available->first());

            return $next($request);
        }

        return redirect()->route('sucursal-context.select');
    }
}
