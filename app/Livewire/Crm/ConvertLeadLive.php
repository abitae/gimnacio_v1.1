<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Membresia;
use App\Models\Crm\Lead;
use App\Services\Crm\ConvertLeadToClientService;
use Livewire\Component;

class ConvertLeadLive extends Component
{
    use FlashesToast;

    public ?int $leadId = null;
    public $tipo_documento = 'DNI';
    public $numero_documento = '';
    public $nombres = '';
    public $apellidos = '';
    public $telefono = '';
    public $email = '';
    public $direccion = '';
    public $activar_membresia = false;
    public $membresia_id = '';
    public $pago_monto = '';
    public $pago_metodo = 'efectivo';

    protected ConvertLeadToClientService $convertService;

    protected $listeners = ['openConvert' => 'setLead'];

    public function mount(?int $leadId = null)
    {
        if ($leadId) {
            $this->setLead($leadId);
        }
    }

    public function boot(ConvertLeadToClientService $convertService)
    {
        $this->convertService = $convertService;
    }

    public function setLead(int $id)
    {
        $this->leadId = $id;
        $lead = Lead::find($id);
        if ($lead) {
            $this->nombres = $lead->nombres ?? '';
            $this->apellidos = $lead->apellidos ?? '';
            $this->telefono = $lead->telefono ?? '';
            $this->email = $lead->email ?? '';
            $this->direccion = $lead->direccion ?? '';
        }
    }

    public function convert()
    {
        $this->authorize('crm.update');
        $this->validate([
            'tipo_documento' => 'required|in:DNI,CE',
            'numero_documento' => 'required|string|max:20',
            'nombres' => 'required|string|max:100',
            'apellidos' => 'required|string|max:100',
            'membresia_id' => 'required_if:activar_membresia,true|nullable|exists:membresias,id',
        ], [
            'numero_documento.required' => 'El número de documento es obligatorio para convertir.',
            'membresia_id.required_if' => 'Debes seleccionar una membresía al activar membresía.',
        ]);

        $lead = Lead::find($this->leadId);
        if (!$lead) {
            $this->flashToast('error', 'Lead no encontrado');
            return;
        }

        try {
            $data = [
                'tipo_documento' => $this->tipo_documento,
                'numero_documento' => $this->numero_documento,
                'nombres' => $this->nombres,
                'apellidos' => $this->apellidos,
                'telefono' => $this->telefono ?: null,
                'email' => $this->email ?: null,
                'direccion' => $this->direccion ?: null,
                'activar_membresia' => $this->activar_membresia,
                'membresia_id' => $this->activar_membresia ? $this->membresia_id : null,
                'pago' => [
                    'monto' => $this->pago_monto ? (float) $this->pago_monto : 0,
                    'metodo_pago' => $this->pago_metodo,
                    'descuento' => 0,
                ],
            ];
            $this->convertService->convert($lead, $data);
            $this->dispatch('convert-done');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->flashToast('error', $e->validator->errors()->first());
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function getLeadProperty(): ?Lead
    {
        return $this->leadId ? Lead::find($this->leadId) : null;
    }

    public function getMembresiasProperty()
    {
        return Membresia::where('estado', 'activa')->orderBy('nombre')->get();
    }

    public function render()
    {
        return view('livewire.crm.convert-lead-live');
    }
}
