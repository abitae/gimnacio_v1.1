<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\Core\Cliente;

/**
 * Identidad puente BioTime: emp_code = cliente.codigo (no el id interno).
 */
final class BioTimeEmpCode
{
    public static function forCliente(Cliente $cliente): ?string
    {
        $codigo = trim((string) ($cliente->codigo ?? ''));

        return $codigo !== '' ? $codigo : null;
    }
}
