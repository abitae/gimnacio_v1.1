<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\CrmStage;
use App\Services\Crm\LeadService;
use Livewire\Component;

class LeadFormLive extends Component
{
    use FlashesToast;

    public ?int $leadId = null;
    public $stage_id = '';
    public $telefono = '';
    public $whatsapp = '';
    public $nombres = '';
    public $apellidos = '';
    public $tipo_documento = '';
    public $numero_documento = '';
    public $email = '';
    public $direccion = '';
    public $canal_origen = '';
    public $sede = '';
    public $interes_principal = '';
    public $assigned_to = '';
    public $notas = '';

    protected LeadService $leadService;

    protected $listeners = ['editLead' => 'loadLead'];

    public function mount(?int $leadId = null)
    {
        $this->loadLead($leadId);
    }

    public function boot(LeadService $leadService)
    {
        $this->leadService = $leadService;
    }

    public function loadLead(?int $id = null)
    {
        if (!$id) {
            $this->resetForm();
            $defaultStage = CrmStage::where('is_default', true)->first();
            $this->stage_id = $defaultStage?->id ?? '';
            return;
        }
        $lead = $this->leadService->find($id);
        if (!$lead) {
            return;
        }
        $this->leadId = $lead->id;
        $this->stage_id = $lead->stage_id;
        $this->telefono = $lead->telefono ?? '';
        $this->whatsapp = $lead->whatsapp ?? '';
        $this->nombres = $lead->nombres ?? '';
        $this->apellidos = $lead->apellidos ?? '';
        $this->tipo_documento = $lead->tipo_documento ?? '';
        $this->numero_documento = $lead->numero_documento ?? '';
        $this->email = $lead->email ?? '';
        $this->direccion = $lead->direccion ?? '';
        $this->canal_origen = $lead->canal_origen ?? '';
        $this->sede = $lead->sede ?? '';
        $this->interes_principal = $lead->interes_principal ?? '';
        $this->assigned_to = $lead->assigned_to ?? '';
        $this->notas = $lead->notas ?? '';
    }

    public function save()
    {
        $this->authorize($this->leadId ? 'crm.update' : 'crm.create');

        $rules = [
            'telefono' => 'required|string|max:20',
            'stage_id' => 'required|exists:crm_stages,id',
        ];
        $this->validate($rules, [
            'telefono.required' => 'El teléfono es obligatorio.',
        ]);

        try {
            if ($this->leadId) {
                $lead = $this->leadService->find($this->leadId);
                $this->leadService->update($lead, $this->getData());
            } else {
                $this->leadService->create($this->getData());
            }
            $this->dispatch('lead-saved');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flashToast('error', $e->validator->errors()->first());
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    protected function getData(): array
    {
        return [
            'stage_id' => (int) $this->stage_id,
            'telefono' => $this->telefono,
            'whatsapp' => $this->whatsapp ?: null,
            'nombres' => $this->nombres ?: null,
            'apellidos' => $this->apellidos ?: null,
            'tipo_documento' => $this->tipo_documento ?: null,
            'numero_documento' => $this->numero_documento ?: null,
            'email' => $this->email ?: null,
            'direccion' => $this->direccion ?: null,
            'canal_origen' => $this->canal_origen ?: null,
            'sede' => $this->sede ?: null,
            'interes_principal' => $this->interes_principal ?: null,
            'assigned_to' => $this->assigned_to ? (int) $this->assigned_to : null,
            'notas' => $this->notas ?: null,
        ];
    }

    public function resetForm()
    {
        $this->leadId = null;
        $this->stage_id = '';
        $this->telefono = '';
        $this->whatsapp = '';
        $this->nombres = '';
        $this->apellidos = '';
        $this->tipo_documento = '';
        $this->numero_documento = '';
        $this->email = '';
        $this->direccion = '';
        $this->canal_origen = '';
        $this->sede = '';
        $this->interes_principal = '';
        $this->assigned_to = '';
        $this->notas = '';
        $this->resetValidation();
    }

    public function getStagesProperty()
    {
        return CrmStage::orderBy('orden')->get();
    }

    public function getUsersProperty()
    {
        return \App\Models\User::orderBy('name')->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.crm.lead-form-live');
    }
}
