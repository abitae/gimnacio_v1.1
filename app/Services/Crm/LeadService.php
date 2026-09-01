<?php

namespace App\Services\Crm;

use App\Models\Crm\Lead;
use App\Models\Crm\CrmStage;
use App\Support\Crm\CrmOwnershipScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

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
                $query->where('codigo', 'like', "%{$term}%")
                    ->orWhere('telefono', 'like', "%{$term}%")
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

        return CrmOwnershipScope::restrictToOwner($q, 'assigned_to');
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
                    $q->where('codigo', 'like', "%{$term}%")
                        ->orWhere('telefono', 'like', "%{$term}%")
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
            CrmOwnershipScope::restrictToOwner($query, 'assigned_to');
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
        if (isset($data['stage_id']) && (int) $data['stage_id'] !== (int) $lead->stage_id) {
            $stage = CrmStage::findOrFail($data['stage_id']);
            if ($lead->isConvertido()) {
                throw new InvalidArgumentException('No se puede cambiar la etapa de un lead convertido.');
            }
            $currentStage = $lead->stage;
            if ($currentStage && ! $this->isValidTransition($currentStage, $stage)) {
                throw new InvalidArgumentException("No se puede mover de «{$currentStage->nombre}» a «{$stage->nombre}».");
            }
            $data['estado'] = $this->mapStageToEstado($stage);
        }

        $lead->update($data);
        return $lead->fresh();
    }

    public function moveToStage(Lead $lead, int $stageId): Lead
    {
        if ($lead->isConvertido()) {
            throw new InvalidArgumentException('No se puede mover un lead convertido.');
        }

        $stage = CrmStage::findOrFail($stageId);
        $currentStage = $lead->stage;

        if ($currentStage && ! $this->isValidTransition($currentStage, $stage)) {
            throw new InvalidArgumentException("No se puede mover de «{$currentStage->nombre}» a «{$stage->nombre}».");
        }

        $lead->update([
            'stage_id' => $stageId,
            'estado' => $this->mapStageToEstado($stage),
        ]);

        return $lead->fresh();
    }

    public function canConvert(Lead $lead): bool
    {
        if ($lead->isConvertido()) {
            return false;
        }

        if (! config('crm.conversion.require_qualified_stage', true)) {
            return true;
        }

        $stage = $lead->stage;
        if (! $stage) {
            return false;
        }

        $minOrden = (int) config('crm.conversion.min_stage_orden', 5);

        return $stage->is_won || $stage->orden >= $minOrden;
    }

    public function assertCanConvert(Lead $lead): void
    {
        if ($lead->isConvertido()) {
            throw new InvalidArgumentException('Este lead ya fue convertido.');
        }

        if (! $this->canConvert($lead)) {
            $stageName = $lead->stage?->nombre ?? 'desconocida';
            throw new InvalidArgumentException("El lead debe estar en etapa calificada para convertir (actual: {$stageName}).");
        }
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

    public function syncLeadStageFromDealOutcome(Lead $lead, string $outcome): Lead
    {
        if ($lead->isConvertido()) {
            return $lead;
        }

        $stageQuery = CrmStage::query()->orderBy('orden');
        $stage = $outcome === 'won'
            ? $stageQuery->where('is_won', true)->first()
            : $stageQuery->where('is_lost', true)->where('nombre', '!=', 'No responde')->first();

        if (! $stage) {
            return $lead;
        }

        $lead->update([
            'stage_id' => $stage->id,
            'estado' => $this->mapStageToEstado($stage),
        ]);

        return $lead->fresh();
    }

    private function isValidTransition(CrmStage $from, CrmStage $to): bool
    {
        if (! config('crm.pipeline.enforce_transitions', true)) {
            return true;
        }

        if ($from->id === $to->id) {
            return true;
        }

        if ($to->is_lost || $to->is_won) {
            return true;
        }

        if ($to->orden >= $from->orden) {
            return true;
        }

        if (config('crm.pipeline.allow_one_step_back', true) && $to->orden === $from->orden - 1) {
            return true;
        }

        return false;
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
