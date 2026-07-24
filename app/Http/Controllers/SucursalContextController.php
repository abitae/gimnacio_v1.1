<?php

namespace App\Http\Controllers;

use App\Services\SucursalContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SucursalContextController extends Controller
{
    public function __construct(
        protected SucursalContext $context
    ) {}

    public function show(Request $request): View
    {
        $user = $request->user();
        $sucursales = $this->context->availableForUser($user);

        return view('auth.select-sucursal', [
            'sucursales' => $sucursales,
            'activeSucursalId' => $this->context->getSucursalId(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'sucursal_id' => ['required', 'integer'],
        ]);

        $sucursal = $this->context->resolveForUser($request->user(), (int) $request->integer('sucursal_id'));

        if (! $sucursal) {
            throw ValidationException::withMessages([
                'sucursal_id' => 'La sucursal seleccionada no está asignada a tu usuario.',
            ]);
        }

        $this->context->activate($sucursal);

        session()->forget([
            'dashboard_last_cliente_id',
        ]);

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Sucursal activa actualizada correctamente.');
    }
}
