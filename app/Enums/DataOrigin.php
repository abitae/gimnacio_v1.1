<?php

namespace App\Enums;

enum DataOrigin: string
{
    case Native = 'native';
    case Import = 'import';
    case Legacy = 'legacy';
    case ImportLegacy = 'import_legacy';

    public function label(): string
    {
        return match ($this) {
            self::Native => 'Nativo',
            self::Import => 'Importado',
            self::Legacy => 'Legacy',
            self::ImportLegacy => 'Importado legacy',
        };
    }
}
