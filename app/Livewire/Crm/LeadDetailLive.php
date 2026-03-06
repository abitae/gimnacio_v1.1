<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmActivity;
use App\Models\Crm\Deal;
use App\Models\Crm\Lead;
use App\Services\Crm\CrmActivityService;
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

    protected LeadService $leadService;
    protected DealService $dealService;
    protected CrmActivityService $activityService;

    public function boot(LeadService $leadService, DealService $dealService, CrmActivityService $activityService)
    {
        $this->leadService = $leadService;
        $this->dealService = $dealService;
        $this->activityService = $activityService;
    }

    public function mount($lead)
    {
        $this->authorize('crm.view');
        $this->leadId = (int) $lead;
    }

    public function getLeadProperty(): ?Lead
    {
        return $this->leadService->find($this->leadId);
    }

    public function openConvertModal()
    {
        $this->modalConvert = true;
    }

    public function closeConvertModal()
    {
        $this->modalConvert = false;
    }

    public function convertDone()
    {
        $this->closeConvertModal();
        $this->flashToast('success', 'Lead convertido a cliente');
    }

    public function openDealModal(?int $dealId = null)
    {
        $this->authorize('crm.create');
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

    public function deleteDeal(int $id)
    {
        $this->authorize('crm.delete');
        $deal = Deal::find($id);
        if ($deal && $deal->lead_id === $this->leadId) {
            $this->dealService->delete($deal);
            $this->flashToast('success', 'Oportunidad eliminada');
        }
    }

    public function openActivityModal(?int $activityId = null)
    {
        $this->authorize('crm.create');
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

    public function deleteActivity(int $id)
    {
        $this->authorize('crm.delete');
        $act = CrmActivity::find($id);
        if ($act && $act->lead_id === $this->leadId) {
            $this->activityService->delete($act);
            $this->flashToast('success', 'Actividad eliminada');
        }
    }

    public function openTaskModal(?int $taskId = null)
    {
        $this->authorize('crm.create');
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

    public function openTagsModal()
    {
        $this->authorize('crm.update');
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
        if (!$lead) {
            return $this->redirect(route('crm.pipeline'), navigate: true);
        }
        return view('livewire.crm.lead-detail-live', [
            'lead' => $lead,
        ]);
    }
}
