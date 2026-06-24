<?php

namespace App\Data\Cliente;

use Illuminate\Support\Collection;

final readonly class ClienteCommercialSummary
{
    /**
     * @param  array<int, \App\Models\Core\ClienteMatricula|\App\Models\Core\ClienteMembresia>  $historialMembresias
     * @param  array<int, \App\Models\Core\ClienteMatricula>  $historialClases
     * @param  array<int, \App\Models\Core\EnrollmentInstallment>  $pendienteCuotaPorMatricula
     */
    public function __construct(
        public array $historialMembresias,
        public array $historialClases,
        public bool $usesLegacyMembresiasHistory,
        public Collection $matriculaOpcionesCobro,
        public array $pendienteCuotaPorMatricula,
        public Collection $cuotasCliente,
        public Collection $matriculasFinancieras,
        public Collection $matriculasConCuotas,
        public float $deudaPlanesPendiente,
        public Collection $matriculasSinCronogramaCuotas,
        public mixed $membresiaActivaFromHistory = null,
    ) {}
}
