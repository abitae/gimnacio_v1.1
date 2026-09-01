<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Crm\Tag;
use App\Models\User;
use App\Services\Cliente\ClienteCrmPortfolioService;
use App\Support\Crm\CrmOwnershipScope;
use Livewire\Component;
use Livewire\WithPagination;

class MiCarteraCrmLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';

    public $asesorFilter = 'me';

    public $tagFilter = '';

    public $soloConTareaVencida = false;

    public $perPage = 20;

    public $modalActivity = false;
    public $editingActivityId = null;

    public $modalTask = false;
    public $editingTaskId = null;

    public $modalDeal = false;
    public $editingDealId = null;

    public $modalTags = false;

    public $modalReasignar = false;
    public $reasignarNuevoAsesorId = '';

    public ?int $activeClienteId = null;

    protected ClienteCrmPortfolioService $portfolioService;

    protected $paginationTheme = 'tailwind';

    public function boot(ClienteCrmPortfolioService $portfolioService)
    {
        $this->portfolioService = $portfolioService;
    }

    public function mount()
    {
        $this->authorize('crm.ver');
        if (! $this->canViewAll) {
            $this->asesorFilter = 'me';
        }
    }

    public function getCanViewAllProperty(): bool
    {
        return CrmOwnershipScope::canViewAll();
    }

    public function getVendedoresProperty()
    {
        if (! $this->canViewAll) {
            return collect();
        }

        return User::permission('crm.ver')->orderBy('name')->get(['id', 'name']);
    }

    public function getTagsProperty()
    {
        return Tag::orderBy('nombre')->get();
    }

    public function openActivityModal(int $clienteId, ?int $activityId = null)
    {
        $this->authorize('crm.crear');
        $this->activeClienteId = $clienteId;
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

    public function openTaskModal(int $clienteId, ?int $taskId = null)
    {
        $this->authorize('crm.crear');
        $this->activeClienteId = $clienteId;
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

    public function openDealModal(int $clienteId, ?int $dealId = null)
    {
        $this->authorize('crm.crear');
        $this->activeClienteId = $clienteId;
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

    public function openTagsModal(int $clienteId)
    {
        $this->activeClienteId = $clienteId;
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

    public function openReasignarModal(int $clienteId)
    {
        $this->authorize('crm.reasignar');
        $this->activeClienteId = $clienteId;
        $cliente = Cliente::find($clienteId);
        $this->reasignarNuevoAsesorId = $cliente?->asesor_crm_id ? (string) $cliente->asesor_crm_id : '';
        $this->modalReasignar = true;
    }

    public function closeReasignarModal()
    {
        $this->modalReasignar = false;
        $this->activeClienteId = null;
    }

    public function reasignar()
    {
        $this->authorize('crm.reasignar');
        if (! $this->activeClienteId) {
            return;
        }
        $cliente = Cliente::findOrFail($this->activeClienteId);
        $this->portfolioService->reassign($cliente, $this->reasignarNuevoAsesorId !== '' ? (int) $this->reasignarNuevoAsesorId : null);
        $this->closeReasignarModal();
        $this->flashToast('success', 'Asesor CRM reasignado');
    }

    public function render()
    {
        $filters = [
            'search' => $this->search,
            'asesor_crm_id' => $this->asesorFilter,
            'tag_id' => $this->tagFilter ?: null,
            'con_tarea_vencida' => $this->soloConTareaVencida,
        ];

        $clientes = $this->portfolioService->paginate($filters, $this->perPage);
        $clientes->setCollection($this->portfolioService->decorateWithTrackingSummary($clientes->getCollection()));

        return view('livewire.crm.mi-cartera-crm-live', ['clientes' => $clientes]);
    }
}
