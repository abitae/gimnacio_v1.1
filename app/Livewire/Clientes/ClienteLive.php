<?php

namespace App\Livewire\Clientes;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Services\ClienteContratoMembresiaService;
use App\Services\ClienteService;
use Livewire\Component;
use Livewire\WithPagination;

class ClienteLive extends Component
{
    use FlashesToast;
    use WithPagination;

    public $search = '';

    public $codigoSearch = '';

    public $estadoFilter = '';

    public $asesorFilter = '';

    public $perPage = 15;

    public bool $mostrarModalContrato = false;

    public ?int $clienteIdContrato = null;

    protected $paginationTheme = 'tailwind';

    protected ClienteService $service;

    protected ClienteContratoMembresiaService $contratoService;

    public function boot(ClienteService $service, ClienteContratoMembresiaService $contratoService): void
    {
        $this->service = $service;
        $this->contratoService = $contratoService;
    }

    public function mount(): void
    {
        $this->authorize('cliente.ver');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCodigoSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function updatingAsesorFilter(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function verPerfil(int $id): void
    {
        $this->redirect(route('clientes.perfil', ['cliente' => $id]), navigate: true);
    }

    public function abrirContrato(int $clienteId): void
    {
        $this->clienteIdContrato = $clienteId;
        $this->mostrarModalContrato = true;
    }

    public function cerrarModalContrato(): void
    {
        $this->mostrarModalContrato = false;
        $this->clienteIdContrato = null;
    }

    public function enviarContratoPorWhatsApp(int $clienteId): void
    {
        try {
            $cliente = Cliente::query()->findOrFail($clienteId);

            if (! $cliente->getWhatsAppUrlWithMessage()) {
                $this->flashToast('error', __('El cliente no tiene teléfono registrado. Añade un número en su ficha para enviar por WhatsApp.'));

                return;
            }

            $mensaje = $this->contratoService->mensajeWhatsAppContrato($cliente);
            $urlConMensaje = $cliente->getWhatsAppUrlWithMessage($mensaje);
            $this->js('window.open('.json_encode($urlConMensaje).', "whatsapp_chat")');

            $result = $this->contratoService->enviarContratoPorWhatsApp($cliente);
            if ($result['success']) {
                $this->flashToast('success', __($result['message']));
            }
        } catch (\Throwable $e) {
            report($e);
            $this->flashToast('error', __('No se pudo preparar el envío por WhatsApp.'));
        }
    }

    public function render()
    {
        $codigoTrim = trim($this->codigoSearch);

        $asesorId = $this->asesorFilter !== '' ? (int) $this->asesorFilter : null;

        if ($this->search || $this->estadoFilter || $codigoTrim !== '' || $asesorId) {
            $clientes = $this->service->search(
                $this->search,
                $this->estadoFilter ?: null,
                $this->perPage,
                $codigoTrim !== '' ? $codigoTrim : null,
                $asesorId,
            );
        } else {
            $clientes = $this->service->paginate($this->perPage);
        }

        return view('livewire.clientes.cliente-live', [
            'clientes' => $clientes,
            'asesores' => $this->service->asesoresActivosParaFiltro(),
        ]);
    }
}
