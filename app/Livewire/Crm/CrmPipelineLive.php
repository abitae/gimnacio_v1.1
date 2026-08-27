<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmStage;
use App\Models\Crm\Lead;
use App\Services\Crm\CrmOperationalSummaryService;
use App\Services\Crm\CrmStageService;
use App\Services\Crm\LeadService;
use Livewire\Component;

class CrmPipelineLive extends Component
{
    use FlashesToast;

    public $search = '';

    public $assignedFilter = '';

    public $canalFilter = '';

    /** Límite de leads por columna en el Kanban */
    public int $perStageLimit = 25;

    public $modalLead = false;

    public $modalConvert = false;

    public $selectedLeadId = null;

    public $editingLeadId = null;

    /** Para mostrar loading al mover lead (leadId => stageId) */
    public $movingLeadId = null;

    public $movingToStageId = null;

    public bool $modalStages = false;

    public bool $stageFormOpen = false;

    public ?int $editingStageId = null;

    public string $stageNombre = '';

    public bool $stageIsDefault = false;

    public bool $stageIsWon = false;

    public bool $stageIsLost = false;

    public bool $confirmDeleteStage = false;

    public ?int $stageIdPendingDelete = null;

    /** @var list<int> */
    public array $collapsedStageIds = [];

    protected LeadService $leadService;

    protected CrmOperationalSummaryService $summaryService;

    protected CrmStageService $stageService;

    public function boot(LeadService $leadService, CrmOperationalSummaryService $summaryService, CrmStageService $stageService)
    {
        $this->leadService = $leadService;
        $this->summaryService = $summaryService;
        $this->stageService = $stageService;
    }

    public function mount()
    {
        $this->authorize('crm.ver');
        $this->collapsedStageIds = $this->normalizedCollapsedIds(
            session('crm.pipeline.collapsed_stages', [])
        );
    }

    public function toggleStageCollapse(int $stageId): void
    {
        $ids = $this->normalizedCollapsedIds($this->collapsedStageIds);

        if (in_array($stageId, $ids, true)) {
            $this->collapsedStageIds = array_values(array_filter(
                $ids,
                fn (int $id) => $id !== $stageId
            ));
        } else {
            $ids[] = $stageId;
            $this->collapsedStageIds = $ids;
        }

        $this->persistCollapsedStages();
    }

    public function expandAllStages(): void
    {
        $this->collapsedStageIds = [];
        $this->persistCollapsedStages();
    }

    public function collapseEmptyStages(): void
    {
        $emptyIds = $this->getStagesProperty()
            ->filter(fn ($stage) => (int) ($stage->leads_count ?? 0) === 0)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->collapsedStageIds = array_values(array_unique([
            ...$this->collapsedStageIds,
            ...$emptyIds,
        ]));
        $this->persistCollapsedStages();
    }

    public function isStageCollapsed(int $stageId): bool
    {
        return in_array($stageId, $this->normalizedCollapsedIds($this->collapsedStageIds), true);
    }

    public function getSummaryProperty(): array
    {
        $assignedTo = $this->assignedFilter === 'me'
            ? auth()->id()
            : ($this->assignedFilter ? (int) $this->assignedFilter : null);

        return $this->summaryService->getSummary($assignedTo);
    }

    public function getStagesProperty()
    {
        return $this->leadService->getStagesForPipeline([
            'search' => $this->search,
            'assigned_to' => $this->assignedFilter,
            'canal_origen' => $this->canalFilter,
        ], $this->perStageLimit);
    }

    public function getManageStagesProperty()
    {
        return $this->stageService->listForManage();
    }

    public function getCanalesProperty()
    {
        return $this->leadService->getDistinctCanales();
    }

    public function openNewLead()
    {
        $this->authorize('crm.crear');
        $this->editingLeadId = null;
        $this->modalLead = true;
    }

    public function openEditLead($id)
    {
        $this->authorize('crm.editar');
        $this->editingLeadId = (int) $id;
        $this->modalLead = true;
    }

    public function openLeadDetail($id)
    {
        $this->redirect(route('crm.leads.show', ['lead' => (int) $id]), navigate: true);
    }

    public function moveToStage(int $leadId, int $stageId)
    {
        $this->authorize('crm.editar');
        $this->movingLeadId = $leadId;
        $this->movingToStageId = $stageId;

        $lead = Lead::find($leadId);
        if (! $lead) {
            $this->movingLeadId = null;
            $this->movingToStageId = null;
            $this->flashToast('error', 'Lead no encontrado');

            return;
        }

        try {
            $this->leadService->moveToStage($lead, $stageId);
            $this->flashToast('success', 'Lead movido de etapa');
        } catch (\InvalidArgumentException $e) {
            $this->flashToast('error', $e->getMessage());
        } finally {
            $this->movingLeadId = null;
            $this->movingToStageId = null;
        }
    }

    public function closeLeadModal()
    {
        $this->modalLead = false;
        $this->editingLeadId = null;
    }

    public function leadSaved()
    {
        $this->closeLeadModal();
        $this->flashToast('success', 'Lead guardado');
    }

    public function openConvertModal($id)
    {
        $lead = Lead::find((int) $id);
        if ($lead) {
            $this->authorize('convert', $lead);
        }
        $this->selectedLeadId = (int) $id;
        $this->modalConvert = true;
    }

    public function closeConvertModal()
    {
        $this->modalConvert = false;
        $this->selectedLeadId = null;
    }

    public function convertDone(?int $clienteId = null)
    {
        $this->closeConvertModal();
        if ($clienteId) {
            return $this->redirect(route('clientes.perfil', $clienteId), navigate: true);
        }
        $this->flashToast('success', 'Lead convertido a cliente');
    }

    public function openManageStages(): void
    {
        $this->authorize('crm.editar');
        $this->stageFormOpen = false;
        $this->editingStageId = null;
        $this->modalStages = true;
    }

    public function closeManageStages(): void
    {
        $this->modalStages = false;
        $this->stageFormOpen = false;
        $this->editingStageId = null;
        $this->resetStageForm();
    }

    public function openCreateStage(): void
    {
        $this->authorize('crm.crear');
        $this->editingStageId = null;
        $this->resetStageForm();
        $this->stageFormOpen = true;
        $this->modalStages = true;
    }

    public function openEditStage(int $id): void
    {
        $this->authorize('crm.editar');
        $stage = CrmStage::find($id);
        if (! $stage) {
            $this->flashToast('error', 'Etapa no encontrada');

            return;
        }

        $this->editingStageId = $id;
        $this->stageNombre = $stage->nombre;
        $this->stageIsDefault = (bool) $stage->is_default;
        $this->stageIsWon = (bool) $stage->is_won;
        $this->stageIsLost = (bool) $stage->is_lost;
        $this->stageFormOpen = true;
        $this->modalStages = true;
    }

    public function backToStageList(): void
    {
        $this->stageFormOpen = false;
        $this->editingStageId = null;
        $this->resetStageForm();
    }

    public function saveStage(): void
    {
        $this->authorize($this->editingStageId ? 'crm.editar' : 'crm.crear');

        $this->validate([
            'stageNombre' => 'required|string|max:80',
            'stageIsDefault' => 'boolean',
            'stageIsWon' => 'boolean',
            'stageIsLost' => 'boolean',
        ], [], [
            'stageNombre' => 'nombre',
        ]);

        $payload = [
            'nombre' => $this->stageNombre,
            'is_default' => $this->stageIsDefault,
            'is_won' => $this->stageIsWon,
            'is_lost' => $this->stageIsLost,
        ];

        try {
            if ($this->editingStageId) {
                $stage = CrmStage::findOrFail($this->editingStageId);
                $this->stageService->update($stage, $payload);
            } else {
                $this->stageService->create($payload);
            }
            $this->flashToast('success', 'Etapa guardada');
            $this->backToStageList();
        } catch (\InvalidArgumentException $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function requestDeleteStage(int $id): void
    {
        $this->authorize('crm.eliminar');
        $this->stageIdPendingDelete = $id;
        $this->confirmDeleteStage = true;
    }

    public function cancelDeleteStage(): void
    {
        $this->confirmDeleteStage = false;
        $this->stageIdPendingDelete = null;
    }

    public function deleteStage(?int $id = null): void
    {
        $this->authorize('crm.eliminar');
        $id ??= $this->stageIdPendingDelete;
        $stage = $id ? CrmStage::find($id) : null;
        if (! $stage) {
            $this->flashToast('error', 'Etapa no encontrada');
            $this->cancelDeleteStage();

            return;
        }

        try {
            $this->stageService->delete($stage);
            $this->flashToast('success', 'Etapa eliminada');
            if ($this->editingStageId === $id) {
                $this->backToStageList();
            }
        } catch (\InvalidArgumentException $e) {
            $this->flashToast('error', $e->getMessage());
        } finally {
            $this->cancelDeleteStage();
        }
    }

    public function moveStageUp(int $id): void
    {
        $this->authorize('crm.editar');
        $stage = CrmStage::find($id);
        if (! $stage) {
            return;
        }
        $this->stageService->moveUp($stage);
    }

    public function moveStageDown(int $id): void
    {
        $this->authorize('crm.editar');
        $stage = CrmStage::find($id);
        if (! $stage) {
            return;
        }
        $this->stageService->moveDown($stage);
    }

    public function updatedStageIsWon(bool $value): void
    {
        if ($value) {
            $this->stageIsLost = false;
        }
    }

    public function updatedStageIsLost(bool $value): void
    {
        if ($value) {
            $this->stageIsWon = false;
        }
    }

    protected function resetStageForm(): void
    {
        $this->stageNombre = '';
        $this->stageIsDefault = false;
        $this->stageIsWon = false;
        $this->stageIsLost = false;
        $this->resetValidation();
    }

    /**
     * @param  mixed  $ids
     * @return list<int>
     */
    protected function normalizedCollapsedIds(mixed $ids): array
    {
        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    protected function persistCollapsedStages(): void
    {
        $this->collapsedStageIds = $this->normalizedCollapsedIds($this->collapsedStageIds);
        session(['crm.pipeline.collapsed_stages' => $this->collapsedStageIds]);
    }

    public function getUsersProperty()
    {
        return \App\Models\User::query()
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.crm.crm-pipeline-live', [
            'stages' => $this->getStagesProperty(),
            'summary' => $this->getSummaryProperty(),
            'manageStages' => $this->modalStages ? $this->getManageStagesProperty() : collect(),
        ]);
    }
}
