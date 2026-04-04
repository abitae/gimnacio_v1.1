<?php

namespace App\Services\Imports;

use InvalidArgumentException;

class SociosActivosImportService
{
    public function __construct(
        private readonly ExcelSociosReader $reader,
        private readonly MembershipCatalogBuilder $membershipCatalogBuilder,
        private readonly SellerUserResolver $sellerUserResolver,
        private readonly ClienteImportProcessor $clienteImportProcessor,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function run(string $phase, string $filePath, bool $execute = false): array
    {
        $rows = $this->reader->read($filePath);

        return match ($phase) {
            'membresias' => $this->membershipCatalogBuilder->sync($rows, $execute),
            'users' => $this->sellerUserResolver->syncUsers($rows, $execute),
            'clients' => $this->clienteImportProcessor->import($rows, $execute),
            default => throw new InvalidArgumentException("Fase no soportada: {$phase}"),
        };
    }
}
