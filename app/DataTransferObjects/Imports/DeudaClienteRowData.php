<?php

namespace App\DataTransferObjects\Imports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

class DeudaClienteRowData
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $codigo,
        public readonly ?string $nombreRaw,
        public readonly ?string $correo,
        public readonly ?string $dni,
        public readonly ?string $celular,
        public readonly ?string $tipoPlan,
        public readonly ?string $plan,
        public readonly ?CarbonImmutable $fechaInicio,
        public readonly ?CarbonImmutable $fechaFin,
        public readonly ?float $costo,
        public readonly ?float $debe,
        public readonly ?string $vendedor,
    ) {}

    public function isPersonalizado(): bool
    {
        return self::normalizeComparable($this->tipoPlan) === 'personalizado';
    }

    public function shouldProcessDebt(): bool
    {
        return ! $this->isPersonalizado() && $this->debe !== null && $this->debe > 0;
    }

    public static function normalizeComparable(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = Str::ascii($value);
        $value = Str::lower($value);
        $value = preg_replace('/[^a-z0-9\+]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        return trim($value);
    }
}
