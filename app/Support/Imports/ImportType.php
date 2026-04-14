<?php

namespace App\Support\Imports;

final class ImportType
{
    public const CLIENTES = 'clientes';

    public const MEMBRESIAS_MATRICULAS = 'membresias_matriculas';

    public const DEUDAS = 'deudas';

    public const CUOTAS = 'cuotas';

    public const USUARIOS = 'usuarios';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::USUARIOS => 'Vendedores / usuarios (columna VENDEDOR en cualquier Excel legacy)',
            self::CLIENTES => 'Clientes (Socios activos.xlsx)',
            self::MEMBRESIAS_MATRICULAS => 'Membresías / Matrículas (Socios activos.xlsx)',
            self::DEUDAS => 'Deudas (Deudas Clientes.xlsx)',
            self::CUOTAS => 'Cuotas (Deudas Cuotas Clientes.xlsx)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function implemented(): array
    {
        return [
            self::USUARIOS,
            self::CLIENTES,
            self::MEMBRESIAS_MATRICULAS,
            self::DEUDAS,
            self::CUOTAS,
        ];
    }
}
