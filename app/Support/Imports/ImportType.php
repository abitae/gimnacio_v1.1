<?php

namespace App\Support\Imports;

final class ImportType
{
    public const SOCIOS_ACTIVOS_INTEGRAL = 'socios_activos_integral';

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
            self::MEMBRESIAS_MATRICULAS => 'Membresias / Matriculas (Socios activos.xlsx)',
            self::CLIENTES => 'Clientes (Socios activos.xlsx)',
            self::DEUDAS => 'Deudas (Deudas Clientes.xlsx)',
        ];
    }

    /**
     * @return list<string>
     */
    public static function implemented(): array
    {
        return [
            self::USUARIOS,
            self::MEMBRESIAS_MATRICULAS,
            self::CLIENTES,
            self::DEUDAS,
        ];
    }
}
