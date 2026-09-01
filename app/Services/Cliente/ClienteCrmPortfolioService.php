<?php

namespace App\Services\Cliente;

use App\Models\Core\Cliente;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmTask;
use App\Models\Crm\Deal;
use App\Support\Crm\CrmOwnershipScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ClienteCrmPortfolioService
{
    public function query(array $filters = []): Builder
    {
        $q = Cliente::query()->with(['asesorCrm', 'crmTags']);

        if (isset($filters['asesor_crm_id'])) {
            if ($filters['asesor_crm_id'] === 'me') {
                $q->where('asesor_crm_id', auth()->id());
            } elseif ($filters['asesor_crm_id'] !== '' && $filters['asesor_crm_id'] !== null) {
                $q->where('asesor_crm_id', $filters['asesor_crm_id']);
            } elseif (! CrmOwnershipScope::canViewAll()) {
                $q->where('asesor_crm_id', auth()->id());
            }
        } elseif (! CrmOwnershipScope::canViewAll()) {
            $q->where('asesor_crm_id', auth()->id());
        }

        if (!empty($filters['search'])) {
            $term = trim($filters['search']);
            $q->where(function (Builder $query) use ($term) {
                $query->where('nombres', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhereRaw("CONCAT(COALESCE(nombres,''), ' ', COALESCE(apellidos,'')) LIKE ?", ["%{$term}%"])
                    ->orWhere('telefono', 'like', "%{$term}%")
                    ->orWhere('numero_documento', 'like', "%{$term}%");
            });
        }

        if (!empty($filters['tag_id'])) {
            $tagId = $filters['tag_id'];
            $q->whereHas('crmTags', fn (Builder $t) => $t->where('tags.id', $tagId));
        }

        if (!empty($filters['con_tarea_vencida'])) {
            $q->whereHas('crmTasks', function (Builder $t) {
                $t->where('estado', 'pending')->where('fecha_hora_programada', '<', now());
            });
        }

        return $q->orderByDesc('updated_at');
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->paginate($perPage);
    }

    public function reassign(Cliente $cliente, ?int $nuevoAsesorId): Cliente
    {
        $cliente->update(['asesor_crm_id' => $nuevoAsesorId]);

        return $cliente->fresh();
    }

    /**
     * Precarga en bloque (evita N+1) la próxima tarea pendiente, última actividad
     * y deals abiertos por cliente, para las tarjetas de la cartera.
     */
    public function decorateWithTrackingSummary(Collection $clientes): Collection
    {
        $ids = $clientes->pluck('id');

        $nextTasks = CrmTask::whereIn('cliente_id', $ids)
            ->where('estado', 'pending')
            ->orderBy('fecha_hora_programada')
            ->get()
            ->groupBy('cliente_id');

        $lastActivities = CrmActivity::whereIn('cliente_id', $ids)
            ->orderByDesc('fecha_hora')
            ->get()
            ->groupBy('cliente_id');

        $openDeals = Deal::whereIn('cliente_id', $ids)
            ->where('estado', 'open')
            ->get()
            ->groupBy('cliente_id');

        return $clientes->map(function (Cliente $cliente) use ($nextTasks, $lastActivities, $openDeals) {
            $cliente->setAttribute('next_task', $nextTasks->get($cliente->id)?->first());
            $cliente->setAttribute('last_activity', $lastActivities->get($cliente->id)?->first());
            $cliente->setAttribute('open_deals', $openDeals->get($cliente->id) ?? collect());

            return $cliente;
        });
    }
}
