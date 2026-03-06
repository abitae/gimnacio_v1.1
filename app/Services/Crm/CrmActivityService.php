<?php

namespace App\Services\Crm;

use App\Models\Crm\CrmActivity;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CrmActivityService
{
    public function query(array $filters = []): Builder
    {
        $q = CrmActivity::query()->with(['user', 'lead', 'cliente', 'deal']);

        if (!empty($filters['lead_id'])) {
            $q->where('lead_id', $filters['lead_id']);
        }
        if (!empty($filters['cliente_id'])) {
            $q->where('cliente_id', $filters['cliente_id']);
        }
        if (!empty($filters['deal_id'])) {
            $q->where('deal_id', $filters['deal_id']);
        }
        if (!empty($filters['type'])) {
            $q->where('tipo', $filters['type']);
        }
        if (!empty($filters['from'])) {
            $q->whereDate('fecha_hora', '>=', $filters['from']);
        }
        if (!empty($filters['to'])) {
            $q->whereDate('fecha_hora', '<=', $filters['to']);
        }

        return $q->orderBy('fecha_hora', 'desc');
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function create(array $data): CrmActivity
    {
        $data['user_id'] = $data['user_id'] ?? auth()->id();
        if (empty($data['fecha_hora'])) {
            $data['fecha_hora'] = now();
        }
        return CrmActivity::create($data);
    }

    public function update(CrmActivity $activity, array $data): CrmActivity
    {
        $activity->update($data);
        return $activity->fresh();
    }

    public function delete(CrmActivity $activity): bool
    {
        return $activity->delete();
    }
}
