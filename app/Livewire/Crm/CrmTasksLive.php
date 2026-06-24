<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmTask;
use App\Models\Crm\Lead;
use App\Services\Crm\CrmTaskService;
use Livewire\Component;
use Livewire\WithPagination;

class CrmTasksLive extends Component
{
    use FlashesToast, WithPagination;

    public $view = 'my-day'; // 'my-day' | 'list'

    public $statusFilter = '';

    public $perPage = 15;

    public $modalTask = false;

    public $taskLeadId = null;

    public $taskClienteId = null;

    public $taskEntityType = 'lead';

    protected CrmTaskService $taskService;

    protected $paginationTheme = 'tailwind';

    public function boot(CrmTaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function mount()
    {
        $this->authorize('crm.ver');
    }

    public function getMyDayProperty(): array
    {
        return $this->taskService->getMyDay(auth()->id());
    }

    public function getAssignableLeadsProperty()
    {
        return Lead::query()
            ->where('estado', '!=', 'convertido')
            ->where(function ($q) {
                $q->where('assigned_to', auth()->id())->orWhereNull('assigned_to');
            })
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get(['id', 'nombres', 'apellidos', 'telefono']);
    }

    public function openNewTask()
    {
        $this->authorize('crm.crear');
        $this->taskLeadId = null;
        $this->taskClienteId = null;
        $this->taskEntityType = 'lead';
        $this->modalTask = true;
    }

    public function closeTaskModal()
    {
        $this->modalTask = false;
        $this->taskLeadId = null;
        $this->taskClienteId = null;
    }

    public function taskSaved()
    {
        $this->closeTaskModal();
        $this->flashToast('success', 'Tarea creada');
    }

    public function completeTask(int $id)
    {
        $this->authorize('crm.editar');
        $task = CrmTask::find($id);
        if (! $task) {
            return;
        }
        $this->taskService->complete($task);
        $this->flashToast('success', 'Tarea marcada como hecha');
    }

    public function render()
    {
        if ($this->view === 'my-day') {
            return view('livewire.crm.crm-tasks-live', [
                'myDay' => $this->getMyDayProperty(),
            ]);
        }
        $filters = ['assigned_to' => 'me'];
        if ($this->statusFilter) {
            $filters['status'] = $this->statusFilter;
        }
        $tasks = $this->taskService->paginate($filters, $this->perPage);

        return view('livewire.crm.crm-tasks-live', [
            'tasks' => $tasks,
            'myDay' => null,
        ]);
    }
}
