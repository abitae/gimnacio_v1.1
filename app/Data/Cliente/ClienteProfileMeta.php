<?php

namespace App\Data\Cliente;

final readonly class ClienteProfileMeta
{
    public function __construct(
        public int $clienteId,
        public bool $usesLegacyMembresiasHistory,
    ) {}
}
