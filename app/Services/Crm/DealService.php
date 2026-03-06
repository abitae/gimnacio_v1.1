<?php

namespace App\Services\Crm;

use App\Models\Crm\Deal;
use App\Models\Crm\LossReason;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class DealService
{
    public function query(array $filters = []): Builder
    {
        $q = Deal::query()->with(['lead', 'cliente', 'membresia', 'assignedTo', 'motivoPerdida']);

        if (!empty($filters['lead_id'])) {
            $q->where('lead_id', $filters['lead_id']);
        }
        if (!empty($filters['cliente_id'])) {
            $q->where('cliente_id', $filters['cliente_id']);
        }
        if (!empty($filters['estado'])) {
            $q->where('estado', $filters['estado']);
        }
        if (isset($filters['assigned_to'])) {
            if ($filters['assigned_to'] === 'me') {
                $q->where('assigned_to', auth()->id());
            } else {
                $q->where('assigned_to', $filters['assigned_to']);
            }
        }
        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $q->where(function ($query) use ($term) {
                $query->whereHas('lead', function ($q) use ($term) {
                    $q->where('nombres', 'like', "%{$term}%")
                        ->orWhere('apellidos', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(COALESCE(nombres,''), ' ', COALESCE(apellidos,'')) LIKE ?", ["%{$term}%"])
                        ->orWhere('telefono', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                })->orWhereHas('cliente', function ($q) use ($term) {
                    $q->where('nombres', 'like', "%{$term}%")
                        ->orWhere('apellidos', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(COALESCE(nombres,''), ' ', COALESCE(apellidos,'')) LIKE ?", ["%{$term}%"])
                        ->orWhere('telefono', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            });
        }

        return $q->orderBy('updated_at', 'desc');
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function find(int $id): ?Deal
    {
        return Deal::with(['lead', 'cliente', 'membresia', 'assignedTo', 'motivoPerdida'])->find($id);
    }

    public function create(array $data): Deal
    {
        $data['created_by'] = $data['created_by'] ?? auth()->id();
        $data['estado'] = $data['estado'] ?? 'open';
        return Deal::create($data);
    }

    public function update(Deal $deal, array $data): Deal
    {
        $deal->update($data);
        return $deal->fresh();
    }

    public function markWon(Deal $deal): Deal
    {
        $deal->update(['estado' => 'won']);
        return $deal->fresh();
    }

    public function markLost(Deal $deal, int $motivoPerdidaId, ?string $observacion = null): Deal
    {
        $deal->update([
            'estado' => 'lost',
            'motivo_perdida_id' => $motivoPerdidaId,
            'notas' => $observacion ? trim($deal->notas . "\n" . $observacion) : $deal->notas,
        ]);
        return $deal->fresh();
    }

    public function delete(Deal $deal): bool
    {
        return $deal->delete();
    }

    public function getLossReasons(): \Illuminate\Database\Eloquent\Collection
    {
        return LossReason::where('activo', true)->orderBy('nombre')->get();
    }
}
