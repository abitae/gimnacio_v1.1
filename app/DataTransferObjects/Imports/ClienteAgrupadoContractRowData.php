<?php

namespace App\DataTransferObjects\Imports;

use Carbon\CarbonImmutable;

final class ClienteAgrupadoContractRowData
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $codigo,
        public readonly ?string $nombreCompleto,
        public readonly ?string $celular,
        public readonly ?string $membresia,
        public readonly ?string $vendedor,
        public readonly ?CarbonImmutable $fechaInicio,
        public readonly ?CarbonImmutable $fechaFin,
        public readonly ?float $precio,
        public readonly ?float $pagado,
        public readonly ?float $deuda,
        public readonly ?float $montoCuota,
        public readonly ?int $cuotasPendientes,
        public readonly ?string $estadoExcel,
    ) {}
}
