<?php

declare(strict_types=1);

namespace App\Services\BioTime;

use App\Models\Core\Cliente;

/**
 * Identidad puente BioTime (un valor, dos nombres):
 * - Laravel → BioTime: se envía cliente.numero_documento; en el reloj es emp_code.
 * - BioTime → Laravel: el sync llega con emp_code y se enlaza a numero_documento.
 * cliente.codigo queda como alias de transición (fichas viejas); no se usa en altas nuevas.
 */
final class BioTimeEmpCode
{
    public static function forCliente(Cliente $cliente): ?string
    {
        $documento = trim((string) ($cliente->numero_documento ?? ''));

        return $documento !== '' ? $documento : null;
    }

    /**
     * @return list<string>
     */
    public static function aliasesForCliente(Cliente $cliente): array
    {
        $canonical = self::forCliente($cliente);
        $codigo = trim((string) ($cliente->codigo ?? ''));
        if ($codigo === '' || $codigo === $canonical) {
            return [];
        }

        return [$codigo];
    }

    /**
     * Códigos que pueden aparecer como emp_code en el reloj (documento + alias).
     *
     * @return list<string>
     */
    public static function lookupKeysForCliente(Cliente $cliente): array
    {
        $keys = [];
        $canonical = self::forCliente($cliente);
        if ($canonical !== null) {
            $keys[] = $canonical;
        }

        foreach (self::aliasesForCliente($cliente) as $alias) {
            $keys[] = $alias;
        }

        return $keys;
    }
}
