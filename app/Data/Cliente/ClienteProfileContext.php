<?php

namespace App\Data\Cliente;

use App\Models\Core\Cliente;

final readonly class ClienteProfileContext
{
    public function __construct(
        public Cliente $cliente,
        public ClienteCommercialSummary $commercial,
        public ClienteWellnessSummary $wellness,
        public ClienteCrmSummary $crm,
        public ClienteOperationsSummary $operations,
        public ClienteFidelitySummary $fidelity,
        public ClienteProfileMeta $meta,
    ) {}
}
