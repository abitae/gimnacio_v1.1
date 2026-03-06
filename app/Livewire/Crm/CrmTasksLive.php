<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmTask;
use App\Services\Crm\CrmTaskService;
use Livewire\Component;
use Livewire\WithPagination;

class CrmTasksLive extends Component
{
    use FlashesToast, WithPagination;

    public $view = 'my-day'; // 'my-day' | 'list'
    public $statusFilter = '';
    public $perPage = 15;

    protected CrmTaskService $taskService;
    protected $paginationTheme = 'tailwind';

    public function boot(CrmTaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function mount()
    {
        $this->authorize('crm.view');
    }

    public function getMyDayProperty(): array
    {
        return $this->taskService->getMyDay(auth()->id());
    }

    public function completeTask(int $id)
    {
        $this->authorize('crm.update');
        $task = CrmTask::find($id);
        if (!$task) {
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
