<?php

namespace App\DataTransferObjects\Imports;

use Carbon\CarbonImmutable;

class CuotaClienteRowData
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $codigo,
        public readonly ?string $nombres,
        public readonly ?string $celular,
        public readonly ?string $membresia,
        public readonly ?CarbonImmutable $fechaInicio,
        public readonly ?CarbonImmutable $fechaFin,
        public readonly ?string $vendedor,
        public readonly ?float $precio,
        public readonly ?float $pago,
        public readonly ?CarbonImmutable $fechaCuota,
        public readonly ?float $debe,
        public readonly ?float $montoCuota,
        public readonly ?string $dni = null,
    ) {}
}
