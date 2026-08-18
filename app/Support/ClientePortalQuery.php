<?php

namespace App\Support;

use App\Models\Core\Cliente;
use App\Models\Core\ClienteAppAccount;
use App\Models\Core\ClienteMatricula;
use App\Models\Core\Pago;
use Illuminate\Database\Eloquent\Builder;

final class ClientePortalQuery
{
    public static function clientes(): Builder
    {
        return Cliente::withoutGlobalScope('active_sucursal');
    }

    public static function accounts(): Builder
    {
        return ClienteAppAccount::query();
    }

    public static function matriculas(): Builder
    {
        return ClienteMatricula::withoutGlobalScope('active_sucursal');
    }

    public static function pagos(): Builder
    {
        return Pago::withoutGlobalScope('active_sucursal');
    }

    public static function findClienteByDocumento(string $tipoDocumento, string $numeroDocumento): ?Cliente
    {
        return self::clientes()
            ->where('tipo_documento', strtoupper(trim($tipoDocumento)))
            ->where('numero_documento', trim($numeroDocumento))
            ->first();
    }
}
