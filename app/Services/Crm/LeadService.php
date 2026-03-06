<?php

namespace App\Services\Crm;

use App\Models\Crm\Lead;
use App\Models\Crm\CrmStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)->orderBy('updated_at', 'desc')->paginate($perPage);
    }

    public function query(array $filters = []): Builder
    {
        $q = Lead::query()->with(['stage', 'assignedTo', 'tags']);

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $q->where(function ($query) use ($term) {
                $query->where('telefono', 'like', "%{$term}%")
                    ->orWhere('whatsapp', 'like', "%{$term}%")
                    ->orWhere('nombres', 'like', "%{$term}%")
                    ->orWhere('apellidos', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
                    ->orWhere('numero_documento', 'like', "%{$term}%");
            });
        }
        if (!empty($filters['stage_id'])) {
            $q->where('stage_id', $filters['stage_id']);
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
        if (!empty($filters['canal_origen'])) {
            $q->where('canal_origen', $filters['canal_origen']);
        }
        if (!empty($filters['fecha_desde'])) {
            $q->whereDate('created_at', '>=', $filters['fecha_desde']);
        }
        if (!empty($filters['fecha_hasta'])) {
            $q->whereDate('created_at', '<=', $filters['fecha_hasta']);
        }

        return $q;
    }

    public function getByStages(): \Illuminate\Support\Collection
    {
        return CrmStage::query()
            ->orderBy('orden')
            ->withCount(['leads' => fn ($q) => $q->whereNull('deleted_at')])
            ->with(['leads' => fn ($q) => $q->orderBy('updated_at', 'desc')->limit(50)])
            ->get();
    }

    /**
     * Stages con leads limitados por columna para el Pipeline Kanban.
     * Aplica filtros (search, assigned_to, canal_origen) y limita a $perStageLimit leads por etapa.
     */
    public function getStagesForPipeline(array $filters, int $perStageLimit = 25): \Illuminate\Support\Collection
    {
        $search = $filters['search'] ?? '';
        $assignedTo = $filters['assigned_to'] ?? null;
        $canal = $filters['canal_origen'] ?? '';

        $applyFilters = function ($query) use ($search, $assignedTo, $canal) {
            if ($search !== '') {
                $term = $search;
                $query->where(function ($q) use ($term) {
                    $q->where('telefono', 'like', "%{$term}%")
                        ->orWhere('whatsapp', 'like', "%{$term}%")
                        ->orWhere('nombres', 'like', "%{$term}%")
                        ->orWhere('apellidos', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('numero_documento', 'like', "%{$term}%");
                });
            }
            if ($assignedTo === 'me') {
                $query->where('assigned_to', auth()->id());
            } elseif ($assignedTo) {
                $query->where('assigned_to', $assignedTo);
            }
            if ($canal !== '') {
                $query->where('canal_origen', $canal);
            }
        };

        return CrmStage::query()
            ->orderBy('orden')
            ->withCount(['leads' => $applyFilters])
            ->with(['leads' => function ($query) use ($applyFilters, $perStageLimit) {
                $applyFilters($query);
                $query->with(['assignedTo', 'tags'])
                    ->orderBy('updated_at', 'desc')
                    ->limit($perStageLimit);
            }])
            ->get();
    }

    /** Canales de origen distintos para filtro del pipeline */
    public function getDistinctCanales(): \Illuminate\Support\Collection
    {
        return Lead::query()
            ->whereNotNull('canal_origen')
            ->where('canal_origen', '!=', '')
            ->distinct()
            ->orderBy('canal_origen')
            ->pluck('canal_origen');
    }

    public function find(int $id): ?Lead
    {
        return Lead::with(['stage', 'assignedTo', 'cliente', 'tags', 'deals.membresia', 'activities.user', 'tasks'])
            ->find($id);
    }

    public function create(array $data): Lead
    {
        return DB::transaction(function () use ($data) {
            $defaultStage = CrmStage::where('is_default', true)->first();
            if ($defaultStage) {
                $data['stage_id'] = $data['stage_id'] ?? $defaultStage->id;
            }
            $data['created_by'] = auth()->id();
            $data['estado'] = $data['estado'] ?? 'nuevo';
            return Lead::create($data);
        });
    }

    public function update(Lead $lead, array $data): Lead
    {
        $lead->update($data);
        return $lead->fresh();
    }

    public function moveToStage(Lead $lead, int $stageId): Lead
    {
        $stage = CrmStage::findOrFail($stageId);
        $lead->update([
            'stage_id' => $stageId,
            'estado' => $this->mapStageToEstado($stage),
        ]);
        return $lead->fresh();
    }

    public function assign(Lead $lead, ?int $userId): Lead
    {
        $lead->update(['assigned_to' => $userId]);
        return $lead->fresh();
    }

    public function delete(Lead $lead): bool
    {
        return $lead->delete();
    }

    public function findDuplicateByTelefono(string $telefono, ?int $excludeId = null): ?Lead
    {
        $q = Lead::where('telefono', $telefono);
        if ($excludeId) {
            $q->where('id', '!=', $excludeId);
        }
        return $q->first();
    }

    public function findExistingClienteByDocumento(?string $tipoDocumento, ?string $numeroDocumento): ?\App\Models\Core\Cliente
    {
        if (!$tipoDocumento || !$numeroDocumento) {
            return null;
        }
        return \App\Models\Core\Cliente::where('tipo_documento', $tipoDocumento)
            ->where('numero_documento', $numeroDocumento)
            ->first();
    }

    private function mapStageToEstado(CrmStage $stage): string
    {
        if ($stage->is_won) {
            return 'ganado';
        }
        if ($stage->is_lost) {
            return in_array($stage->nombre, ['No responde'], true) ? 'no_responde' : 'perdido';
        }
        $map = [
            'Nuevo' => 'nuevo',
            'Contactado' => 'contactado',
            'Interesado' => 'interesado',
            'Agendó visita' => 'agendo_visita',
            'Visitó/Prueba' => 'visito',
            'Negociación' => 'negociacion',
        ];
        return $map[$stage->nombre] ?? 'nuevo';
    }
}
