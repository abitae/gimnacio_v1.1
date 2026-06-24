<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Lead;
use App\Services\ClienteService;
use App\Services\Crm\LeadService;
use App\Services\CrmMensajeService;
use Livewire\Component;
use Livewire\WithPagination;

class MensajesLive extends Component
{
    use FlashesToast, WithPagination;

    public string $contactMode = 'cliente';

    public $clienteSearch = '';

    public $clientes;

    public $selectedClienteId = null;

    public $selectedCliente = null;

    public $leadSearch = '';

    public $leads;

    public $selectedLeadId = null;

    public $selectedLead = null;

    public $isSearching = false;

    public $contenido = '';

    public $canalFilter = '';

    public $perPage = 15;

    protected $paginationTheme = 'tailwind';

    protected CrmMensajeService $crmService;

    protected ClienteService $clienteService;

    protected LeadService $leadService;

    public function boot(CrmMensajeService $crmService, ClienteService $clienteService, LeadService $leadService)
    {
        $this->crmService = $crmService;
        $this->clienteService = $clienteService;
        $this->leadService = $leadService;
    }

    public function mount()
    {
        $this->authorize('crm_mensaje.ver');
        $this->clientes = collect([]);
        $this->leads = collect([]);
    }

    public function updatedContactMode()
    {
        $this->clearCliente();
        $this->clearLead();
        $this->resetPage();
    }

    public function updatingClienteSearch()
    {
        $this->isSearching = true;
    }

    public function updatedClienteSearch()
    {
        $term = trim($this->clienteSearch);
        $this->clientes = strlen($term) >= 2 ? $this->clienteService->quickSearch($term, 10) : collect([]);
        $this->isSearching = false;
    }

    public function updatingLeadSearch()
    {
        $this->isSearching = true;
    }

    public function updatedLeadSearch()
    {
        $term = trim($this->leadSearch);
        if (strlen($term) < 2) {
            $this->leads = collect([]);
            $this->isSearching = false;

            return;
        }
        $this->leads = $this->leadService->query(['search' => $term])
            ->where('estado', '!=', 'convertido')
            ->limit(10)
            ->get();
        $this->isSearching = false;
    }

    public function selectCliente($id)
    {
        $this->selectedClienteId = $id;
        $this->selectedCliente = $this->clienteService->find($id);
        $this->clienteSearch = $this->selectedCliente->nombres.' '.$this->selectedCliente->apellidos;
        $this->clientes = collect([]);
        $this->resetPage();
    }

    public function selectLead($id)
    {
        $this->selectedLeadId = $id;
        $this->selectedLead = Lead::find($id);
        $this->leadSearch = $this->selectedLead?->nombre_completo ?? '';
        $this->leads = collect([]);
        $this->resetPage();
    }

    public function clearCliente()
    {
        $this->selectedClienteId = null;
        $this->selectedCliente = null;
        $this->clienteSearch = '';
        $this->clientes = collect([]);
        $this->contenido = '';
        $this->resetPage();
    }

    public function clearLead()
    {
        $this->selectedLeadId = null;
        $this->selectedLead = null;
        $this->leadSearch = '';
        $this->leads = collect([]);
        $this->contenido = '';
        $this->resetPage();
    }

    public function enviarWhatsApp()
    {
        if (! auth()->user()->can('crm_mensaje.enviar') && ! auth()->user()->can('crm_mensaje.crear')) {
            abort(403);
        }

        try {
            if (empty(trim($this->contenido))) {
                $this->flashToast('error', 'Escribe el mensaje');

                return;
            }

            if ($this->contactMode === 'lead') {
                if (! $this->selectedLeadId) {
                    $this->flashToast('error', 'Selecciona un lead');

                    return;
                }
                $this->crmService->enviarWhatsAppLead($this->selectedLeadId, trim($this->contenido), auth()->id());
            } else {
                if (! $this->selectedClienteId) {
                    $this->flashToast('error', 'Selecciona un cliente');

                    return;
                }
                $this->crmService->enviarWhatsApp($this->selectedClienteId, trim($this->contenido), auth()->id());
            }

            $this->flashToast('success', 'Mensaje enviado por WhatsApp');
            $this->contenido = '';
            $this->resetPage();
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $mensajes = $this->contactMode === 'lead'
            ? $this->crmService->getByLead($this->selectedLeadId, array_filter(['canal' => $this->canalFilter ?: null]), $this->perPage)
            : $this->crmService->getByCliente($this->selectedClienteId, array_filter(['canal' => $this->canalFilter ?: null]), $this->perPage);

        return view('livewire.crm.mensajes-live', ['mensajes' => $mensajes]);
    }
}
