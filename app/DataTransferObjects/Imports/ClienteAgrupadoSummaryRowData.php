<?php

namespace App\DataTransferObjects\Imports;

final class ClienteAgrupadoSummaryRowData
{
    public function __construct(
        public readonly int $rowNumber,
        public readonly ?string $codigo,
        public readonly ?float $precioTotal,
        public readonly ?float $pagado,
        public readonly ?float $deudaTotal,
    ) {}
}
