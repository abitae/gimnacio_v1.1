<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use Livewire\Component;

class ClienteTagsLive extends Component
{
    use FlashesToast;

    public int $clienteId;

    protected $listeners = ['tags-saved' => 'onTagsSaved'];

    public function onTagsSaved()
    {
        $this->flashToast('success', 'Etiquetas actualizadas');
    }

    public function mount($cliente)
    {
        $this->authorize('crm.view');
        $this->clienteId = (int) $cliente;
    }

    public function getClienteProperty(): ?Cliente
    {
        return Cliente::find($this->clienteId);
    }

    public function render()
    {
        $cliente = $this->getClienteProperty();
        if (!$cliente) {
            return $this->redirect(route('clientes.index'), navigate: true);
        }
        return view('livewire.crm.cliente-tags-live', ['cliente' => $cliente]);
    }
}
