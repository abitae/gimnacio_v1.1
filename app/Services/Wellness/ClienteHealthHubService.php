<?php

namespace App\Services\Wellness;

use App\Models\Core\Cliente;
use App\Models\Core\HealthRecord;
use App\Services\EvaluacionMedidasNutricionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ClienteHealthHubService
{
    public function __construct(
        protected EvaluacionMedidasNutricionService $evaluacionService,
    ) {}

    public function getHealthRecord(int $clienteId): ?HealthRecord
    {
        return Cliente::query()
            ->with('healthRecord')
            ->find($clienteId)
            ?->healthRecord;
    }

    public function getLatestEvaluations(int $clienteId, int $limit = 5): Collection
    {
        return $this->evaluacionService
            ->getByCliente($clienteId, [], $limit)
            ->getCollection();
    }

    public function getEvaluationHistory(int $clienteId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->evaluacionService->getByCliente($clienteId, [], $perPage);
    }
}
