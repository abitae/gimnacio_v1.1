<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\Core\Cliente;

/**
 * Identidad puente BioTime: un valor.
 * Laravel envía numero_documento; en el reloj es emp_code.
 * cliente.codigo no participa en la sincronización.
 */
final class BioTimeEmpCode
{
    public static function forCliente(Cliente $cliente): ?string
    {
        $documento = trim((string) ($cliente->numero_documento ?? ''));

        return $documento !== '' ? $documento : null;
    }

    /**
     * Claves de emp_code para lookup en espejo BioTime (solo documento).
     *
     * @return list<string>
     */
    public static function lookupKeysForCliente(Cliente $cliente): array
    {
        $canonical = self::forCliente($cliente);

        return $canonical !== null ? [$canonical] : [];
    }
}
