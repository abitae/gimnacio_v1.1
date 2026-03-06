<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmTask;
use App\Services\Crm\CrmTaskService;
use Livewire\Component;

class TaskFormLive extends Component
{
    use FlashesToast;

    public ?int $taskId = null;
    public ?int $leadId = null;
    public ?int $clienteId = null;
    public $tipo = 'call';
    public $fecha_hora_programada = '';
    public $prioridad = 'medium';
    public $notas = '';
    public $assigned_to = '';

    protected CrmTaskService $taskService;

    public function boot(CrmTaskService $taskService)
    {
        $this->taskService = $taskService;
    }

    public function mount(?int $leadId = null, ?int $clienteId = null, ?int $taskId = null)
    {
        $this->leadId = $leadId;
        $this->clienteId = $clienteId;
        $this->taskId = $taskId;
        $this->fecha_hora_programada = now()->addHour()->format('Y-m-d\TH:i');
        $this->assigned_to = (string) auth()->id();
        if ($taskId) {
            $t = CrmTask::find($taskId);
            if ($t) {
                $this->tipo = $t->tipo;
                $this->fecha_hora_programada = $t->fecha_hora_programada->format('Y-m-d\TH:i');
                $this->prioridad = $t->prioridad ?? 'medium';
                $this->notas = $t->notas ?? '';
                $this->assigned_to = $t->assigned_to ? (string) $t->assigned_to : (string) auth()->id();
            }
        }
    }

    public function save()
    {
        $this->validate([
            'tipo' => 'required|in:call,whatsapp,schedule_visit,send_promo,follow_up,other',
            'fecha_hora_programada' => 'required|date',
        ], [], ['fecha_hora_programada' => 'fecha y hora']);
        if (!$this->leadId && !$this->clienteId) {
            $this->flashToast('error', 'Debe indicar lead o cliente');
            return;
        }
        try {
            $data = [
                'lead_id' => $this->leadId,
                'cliente_id' => $this->clienteId,
                'tipo' => $this->tipo,
                'fecha_hora_programada' => $this->fecha_hora_programada,
                'prioridad' => $this->prioridad ?: 'medium',
                'notas' => $this->notas ?: null,
                'assigned_to' => $this->assigned_to ? (int) $this->assigned_to : auth()->id(),
            ];
            if ($this->taskId) {
                $task = CrmTask::findOrFail($this->taskId);
                $this->taskService->update($task, $data);
            } else {
                $this->taskService->create($data);
            }
            $this->dispatch('task-saved');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function getTiposProperty(): array
    {
        return CrmTask::TIPOS;
    }

    public function getPrioridadesProperty(): array
    {
        return CrmTask::PRIORIDADES;
    }

    public function getUsersProperty()
    {
        return \App\Models\User::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.crm.task-form-live');
    }
}
