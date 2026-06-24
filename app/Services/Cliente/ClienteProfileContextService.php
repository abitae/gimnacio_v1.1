<?php

namespace App\Services\Cliente;

use App\Data\Cliente\ClienteCommercialSummary;
use App\Data\Cliente\ClienteProfileContext;
use App\Data\Cliente\ClienteProfileMeta;
use App\Models\Core\Cliente;
use App\Services\ClienteService;

class ClienteProfileContextService
{
    /** @var array<string, ClienteProfileContext|ClienteCommercialSummary> */
    protected array $cache = [];

    public function __construct(
        protected ClienteService $clienteService,
        protected ClienteOperationsProfileService $operationsProfileService,
        protected ClienteCommercialProfileService $commercialProfileService,
        protected ClienteWellnessProfileService $wellnessProfileService,
        protected ClienteCrmProfileService $crmProfileService,
        protected ClienteFidelityProfileService $fidelityProfileService,
    ) {}

    public function clearCache(?int $clienteId = null): void
    {
        if ($clienteId === null) {
            $this->cache = [];

            return;
        }

        foreach (array_keys($this->cache) as $key) {
            if (str_starts_with($key, $clienteId.':')) {
                unset($this->cache[$key]);
            }
        }
    }

    /**
     * @param  list<string>  $sections  operations|commercial|wellness|crm|fidelity
     */
    public function build(int $clienteId, array $sections = ['operations', 'commercial', 'wellness', 'crm', 'fidelity']): ClienteProfileContext
    {
        $withHistory = in_array('commercial', $sections, true);
        $cacheKey = $clienteId.':'.implode(',', $sections);

        if (isset($this->cache[$cacheKey]) && $this->cache[$cacheKey] instanceof ClienteProfileContext) {
            return $this->cache[$cacheKey];
        }

        $cliente = $this->clienteService->find($clienteId);
        $cliente?->loadMissing(['healthRecord', 'trainerUser']);

        $operations = in_array('operations', $sections, true)
            ? $this->operationsProfileService->getSummary($clienteId)
            : $this->emptyOperations();

        $commercial = $this->resolveCommercialSummary($clienteId, $withHistory);

        $wellness = in_array('wellness', $sections, true)
            ? $this->wellnessProfileService->getSummary($clienteId)
            : new \App\Data\Cliente\ClienteWellnessSummary([], 0, 0, 0);

        $crm = in_array('crm', $sections, true)
            ? $this->crmProfileService->getSummary($clienteId)
            : new \App\Data\Cliente\ClienteCrmSummary(0, 0, null, null);

        $fidelity = in_array('fidelity', $sections, true)
            ? $this->fidelityProfileService->getSummary($clienteId)
            : new \App\Data\Cliente\ClienteFidelitySummary([]);

        $context = new ClienteProfileContext(
            cliente: $cliente ?? new Cliente(['id' => $clienteId]),
            commercial: $commercial,
            wellness: $wellness,
            crm: $crm,
            operations: $operations,
            fidelity: $fidelity,
            meta: new ClienteProfileMeta(
                clienteId: $clienteId,
                usesLegacyMembresiasHistory: $commercial->usesLegacyMembresiasHistory,
            ),
        );

        $this->cache[$cacheKey] = $context;

        return $context;
    }

    public function loadCommercialHistory(int $clienteId): ClienteCommercialSummary
    {
        $headerKey = $clienteId.':operations,commercial_debt,wellness,crm,fidelity';
        $fullKey = $clienteId.':operations,commercial,wellness,crm,fidelity';

        $full = $this->commercialProfileService->getSummary($clienteId, withHistory: true);
        $this->cache[$clienteId.':commercial_full'] = $full;

        if (isset($this->cache[$fullKey]) && $this->cache[$fullKey] instanceof ClienteProfileContext) {
            $existing = $this->cache[$fullKey];
            $this->cache[$fullKey] = new ClienteProfileContext(
                cliente: $existing->cliente,
                commercial: $full,
                wellness: $existing->wellness,
                crm: $existing->crm,
                operations: $existing->operations,
                fidelity: $existing->fidelity,
                meta: new ClienteProfileMeta($clienteId, $full->usesLegacyMembresiasHistory),
            );
        }

        return $full;
    }

    protected function resolveCommercialSummary(int $clienteId, bool $withHistory): ClienteCommercialSummary
    {
        if (! $withHistory) {
            $debtKey = $clienteId.':commercial_debt';
            if (isset($this->cache[$debtKey]) && $this->cache[$debtKey] instanceof ClienteCommercialSummary) {
                return $this->cache[$debtKey];
            }

            $summary = $this->commercialProfileService->getSummary($clienteId, withHistory: false);
            $this->cache[$debtKey] = $summary;

            return $summary;
        }

        return $this->commercialProfileService->getSummary($clienteId, withHistory: true);
    }

    protected function emptyOperations(): \App\Data\Cliente\ClienteOperationsSummary
    {
        return new \App\Data\Cliente\ClienteOperationsSummary(
            membresiaActiva: null,
            operacionDiariaDebtSummary: [],
            saldoPendiente: 0.0,
            deudaProductoPendiente: 0.0,
            deudaMembresiaPendiente: 0.0,
            asistenciasRecientes: [],
            estadisticasAsistencia: [],
            validacionAcceso: [],
            ingresoEnCurso: null,
            pagosRecientes: [],
        );
    }
}
