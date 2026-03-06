<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Deal;
use App\Models\Crm\LossReason;
use App\Models\Core\Membresia;
use App\Services\Crm\DealService;
use Livewire\Component;

class DealFormLive extends Component
{
    use FlashesToast;

    public ?int $dealId = null;
    public int $leadId;
    public $membresia_id = '';
    public $precio_objetivo = '';
    public $descuento_sugerido = '0';
    public $probabilidad = 50;
    public $fecha_estimada_cierre = '';
    public $motivo_interes = '';
    public $objeciones = '';
    public $notas = '';
    public $assigned_to = '';

    public $showMarkLost = false;
    public $motivo_perdida_id = '';
    public $observacion_perdida = '';

    protected DealService $dealService;

    public function boot(DealService $dealService)
    {
        $this->dealService = $dealService;
    }

    public function mount(int $leadId, ?int $dealId = null)
    {
        $this->leadId = $leadId;
        $this->dealId = $dealId;
        if ($dealId) {
            $deal = $this->dealService->find($dealId);
            if ($deal) {
                $this->membresia_id = $deal->membresia_id ?? '';
                $this->precio_objetivo = $deal->precio_objetivo;
                $this->descuento_sugerido = $deal->descuento_sugerido ?? '0';
                $this->probabilidad = $deal->probabilidad ?? 50;
                $this->fecha_estimada_cierre = $deal->fecha_estimada_cierre?->format('Y-m-d') ?? '';
                $this->motivo_interes = $deal->motivo_interes ?? '';
                $this->objeciones = $deal->objeciones ?? '';
                $this->notas = $deal->notas ?? '';
                $this->assigned_to = $deal->assigned_to ?? '';
            }
        } else {
            $this->fecha_estimada_cierre = now()->addDays(14)->format('Y-m-d');
        }
    }

    public function save()
    {
        $this->validate([
            'precio_objetivo' => 'required|numeric|min:0',
            'probabilidad' => 'nullable|integer|min:0|max:100',
        ]);
        try {
            $data = [
                'lead_id' => $this->leadId,
                'membresia_id' => $this->membresia_id ? (int) $this->membresia_id : null,
                'precio_objetivo' => (float) $this->precio_objetivo,
                'descuento_sugerido' => (float) ($this->descuento_sugerido ?: 0),
                'probabilidad' => (int) ($this->probabilidad ?: 0),
                'fecha_estimada_cierre' => $this->fecha_estimada_cierre ?: null,
                'motivo_interes' => $this->motivo_interes ?: null,
                'objeciones' => $this->objeciones ?: null,
                'notas' => $this->notas ?: null,
                'assigned_to' => $this->assigned_to ? (int) $this->assigned_to : null,
            ];
            if ($this->dealId) {
                $deal = Deal::findOrFail($this->dealId);
                $this->dealService->update($deal, $data);
            } else {
                $this->dealService->create($data);
            }
            $this->dispatch('deal-saved');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function markWon()
    {
        if (!$this->dealId) {
            return;
        }
        $deal = Deal::findOrFail($this->dealId);
        $this->dealService->markWon($deal);
        $this->dispatch('deal-saved');
    }

    public function markLost()
    {
        $this->validate(['motivo_perdida_id' => 'required|exists:loss_reasons,id']);
        $deal = Deal::findOrFail($this->dealId);
        $this->dealService->markLost($deal, (int) $this->motivo_perdida_id, $this->observacion_perdida ?: null);
        $this->showMarkLost = false;
        $this->dispatch('deal-saved');
    }

    public function getMembresiasProperty()
    {
        return Membresia::where('estado', 'activa')->orderBy('nombre')->get();
    }

    public function getLossReasonsProperty()
    {
        return $this->dealService->getLossReasons();
    }

    public function getUsersProperty()
    {
        return \App\Models\User::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.crm.deal-form-live');
    }
}
