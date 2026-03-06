<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmActivity;
use App\Services\Crm\CrmActivityService;
use Livewire\Component;

class ActivityFormLive extends Component
{
    use FlashesToast;

    public ?int $activityId = null;
    public ?int $leadId = null;
    public ?int $clienteId = null;
    public $tipo = 'call';
    public $fecha_hora = '';
    public $resultado = '';
    public $observaciones = '';

    protected CrmActivityService $activityService;

    public function boot(CrmActivityService $activityService)
    {
        $this->activityService = $activityService;
    }

    public function mount(?int $leadId = null, ?int $clienteId = null, ?int $activityId = null)
    {
        $this->leadId = $leadId;
        $this->clienteId = $clienteId;
        $this->activityId = $activityId;
        $this->fecha_hora = now()->format('Y-m-d\TH:i');
        if ($activityId) {
            $a = CrmActivity::find($activityId);
            if ($a) {
                $this->tipo = $a->tipo;
                $this->fecha_hora = $a->fecha_hora->format('Y-m-d\TH:i');
                $this->resultado = $a->resultado ?? '';
                $this->observaciones = $a->observaciones ?? '';
            }
        }
    }

    public function save()
    {
        $this->validate([
            'tipo' => 'required|in:call,whatsapp,visit,trial,email,note,other',
            'fecha_hora' => 'required|date',
        ], [], ['fecha_hora' => 'fecha y hora']);
        if (!$this->leadId && !$this->clienteId) {
            $this->flashToast('error', 'Debe indicar lead o cliente');
            return;
        }
        try {
            $data = [
                'lead_id' => $this->leadId,
                'cliente_id' => $this->clienteId,
                'tipo' => $this->tipo,
                'fecha_hora' => $this->fecha_hora,
                'resultado' => $this->resultado ?: null,
                'observaciones' => $this->observaciones ?: null,
            ];
            if ($this->activityId) {
                $act = CrmActivity::findOrFail($this->activityId);
                $this->activityService->update($act, $data);
            } else {
                $this->activityService->create($data);
            }
            $this->dispatch('activity-saved');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function getTiposProperty(): array
    {
        return CrmActivity::TIPOS;
    }

    public function render()
    {
        return view('livewire.crm.activity-form-live');
    }
}
