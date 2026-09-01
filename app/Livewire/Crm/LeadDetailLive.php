<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\CrmTask;
use App\Models\Crm\Deal;
use App\Models\Crm\Lead;
use App\Services\Crm\CrmActivityService;
use App\Services\Crm\CrmTaskService;
use App\Services\Crm\DealService;
use App\Services\Crm\LeadService;
use Livewire\Component;

class LeadDetailLive extends Component
{
    use FlashesToast;

    public int $leadId;

    public $modalConvert = false;

    public $modalDeal = false;

    public $modalActivity = false;

    public $modalTags = false;

    public $modalTask = false;

    public $editingDealId = null;

    public $editingActivityId = null;

    public $editingTaskId = null;

    public bool $confirmDeleteDeal = false;

    public ?int $dealIdPendingDelete = null;

    public bool $confirmDeleteActivity = false;

    public ?int $activityIdPendingDelete = null;

    protected LeadService $leadService;

    protected DealService $dealService;

    protected CrmActivityService $activityService;

    protected CrmTaskService $taskService;

    public function boot(LeadService $leadService, DealService $dealService, CrmActivityService $activityService, CrmTaskService $taskService)
    {
        $this->leadService = $leadService;
        $this->dealService = $dealService;
        $this->activityService = $activityService;
        $this->taskService = $taskService;
    }

    public function mount(Lead|int|string $lead): void
    {
        $leadModel = $lead instanceof Lead ? $lead : Lead::findOrFail($lead);
        $this->authorize('view', $leadModel);
        $this->leadId = $leadModel->getKey();
    }

    public function getLeadProperty(): ?Lead
    {
        return $this->leadService->find($this->leadId);
    }

    public function openConvertModal()
    {
        $lead = $this->getLeadProperty();
        if ($lead) {
            $this->authorize('convert', $lead);
        }
        $this->modalConvert = true;
    }

    public function closeConvertModal()
    {
        $this->modalConvert = false;
    }

    public function convertDone()
    {
        $this->closeConvertModal();
        $lead = $this->getLeadProperty();
        if ($lead?->cliente_id) {
            return $this->redirect(route('clientes.perfil', $lead->cliente_id), navigate: true);
        }
        $this->flashToast('success', 'Lead convertido a cliente');
    }

    public function openDealModal(?int $dealId = null)
    {
        $this->authorize('crm.crear');
        $this->editingDealId = $dealId;
        $this->modalDeal = true;
    }

    public function closeDealModal()
    {
        $this->modalDeal = false;
        $this->editingDealId = null;
    }

    public function dealSaved()
    {
        $this->closeDealModal();
        $this->flashToast('success', 'Oportunidad guardada');
    }

    public function requestDeleteDeal(int $id): void
    {
        $deal = Deal::findOrFail($id);
        $this->authorize('delete', $deal);
        $this->dealIdPendingDelete = $id;
        $this->confirmDeleteDeal = true;
    }

    public function cancelDeleteDeal(): void
    {
        $this->confirmDeleteDeal = false;
        $this->dealIdPendingDelete = null;
    }

    public function deleteDeal(?int $id = null)
    {
        $id ??= $this->dealIdPendingDelete;
        $deal = $id ? Deal::find($id) : null;
        if ($deal && $deal->lead_id === $this->leadId) {
            $this->authorize('delete', $deal);
            $this->dealService->delete($deal);
            $this->flashToast('success', 'Oportunidad eliminada');
        }
        $this->cancelDeleteDeal();
    }

    public function openActivityModal(?int $activityId = null)
    {
        $this->authorize('crm.crear');
        $this->editingActivityId = $activityId;
        $this->modalActivity = true;
    }

    public function closeActivityModal()
    {
        $this->modalActivity = false;
        $this->editingActivityId = null;
    }

    public function activitySaved()
    {
        $this->closeActivityModal();
        $this->flashToast('success', 'Actividad guardada');
    }

    public function requestDeleteActivity(int $id): void
    {
        $act = CrmActivity::findOrFail($id);
        $this->authorize('delete', $act);
        $this->activityIdPendingDelete = $id;
        $this->confirmDeleteActivity = true;
    }

    public function cancelDeleteActivity(): void
    {
        $this->confirmDeleteActivity = false;
        $this->activityIdPendingDelete = null;
    }

    public function deleteActivity(?int $id = null)
    {
        $id ??= $this->activityIdPendingDelete;
        $act = $id ? CrmActivity::find($id) : null;
        if ($act && $act->lead_id === $this->leadId) {
            $this->authorize('delete', $act);
            $this->activityService->delete($act);
            $this->flashToast('success', 'Actividad eliminada');
        }
        $this->cancelDeleteActivity();
    }

    public function openTaskModal(?int $taskId = null)
    {
        $this->authorize('crm.crear');
        $this->editingTaskId = $taskId;
        $this->modalTask = true;
    }

    public function closeTaskModal()
    {
        $this->modalTask = false;
        $this->editingTaskId = null;
    }

    public function taskSaved()
    {
        $this->closeTaskModal();
        $this->flashToast('success', 'Tarea guardada');
    }

    public function completeTask(int $id)
    {
        $task = CrmTask::find($id);
        if (! $task || $task->lead_id !== $this->leadId) {
            return;
        }
        $this->authorize('update', $task);
        $this->taskService->complete($task);
        $this->flashToast('success', 'Tarea marcada como hecha');
    }

    public function openTagsModal()
    {
        $lead = $this->getLeadProperty();
        if ($lead) {
            $this->authorize('update', $lead);
        }
        $this->modalTags = true;
    }

    public function closeTagsModal()
    {
        $this->modalTags = false;
    }

    public function tagsSaved()
    {
        $this->closeTagsModal();
        $this->flashToast('success', 'Etiquetas actualizadas');
    }

    public function render()
    {
        $lead = $this->getLeadProperty();
        if (! $lead) {
            return $this->redirect(route('crm.pipeline'), navigate: true);
        }

        return view('livewire.crm.lead-detail-live', [
            'lead' => $lead,
        ]);
    }
}
