<?php

namespace App\Livewire\Concerns;

use App\Services\ClienteCrossSucursalAlertService;
use Illuminate\Support\Collection;

trait ShowsCrossSucursalAlert
{
    /** @var array<int, array<string, mixed>> */
    public array $crossSucursalMatches = [];

    protected function refreshCrossSucursalAlert(?string $tipoDocumento, ?string $numeroDocumento, ?int $excludeClienteId = null): void
    {
        $this->crossSucursalMatches = app(ClienteCrossSucursalAlertService::class)
            ->findMatches($tipoDocumento, $numeroDocumento, $excludeClienteId)
            ->all();
    }

    protected function refreshCrossSucursalAlertForCliente(?\App\Models\Core\Cliente $cliente): void
    {
        if (! $cliente) {
            $this->crossSucursalMatches = [];

            return;
        }

        $this->crossSucursalMatches = app(ClienteCrossSucursalAlertService::class)
            ->findMatchesForCliente($cliente)
            ->all();
    }

    protected function clearCrossSucursalAlert(): void
    {
        $this->crossSucursalMatches = [];
    }
}
