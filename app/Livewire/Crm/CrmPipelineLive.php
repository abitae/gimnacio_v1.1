<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Lead;
use App\Services\Crm\CrmOperationalSummaryService;
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

    protected LeadService $leadService;

    protected CrmOperationalSummaryService $summaryService;

    public function boot(LeadService $leadService, CrmOperationalSummaryService $summaryService)
    {
        $this->leadService = $leadService;
        $this->summaryService = $summaryService;
    }

    public function mount()
    {
        $this->authorize('crm.ver');
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
        ]);
    }
}
