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

    public $vigenciaFilter = '';

    public $membresiaFilter = '';

    public int $ventanaDias = 15;

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

    public function updatingVigenciaFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMembresiaFilter(): void
    {
        $this->resetPage();
    }

    public function updatingVentanaDias(): void
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

    /**
     * @return array<string, mixed>
     */
    protected function exportQueryParams(): array
    {
        $codigoTrim = trim($this->codigoSearch);

        return array_filter([
            'search' => $this->search ?: null,
            'codigo' => $codigoTrim !== '' ? $codigoTrim : null,
            'estado' => $this->estadoFilter ?: null,
            'asesor_id' => $this->asesorFilter !== '' ? (int) $this->asesorFilter : null,
            'vigencia' => $this->vigenciaFilter ?: null,
            'membresia_id' => $this->membresiaFilter !== '' ? (int) $this->membresiaFilter : null,
            'ventana_dias' => $this->vigenciaFilter === 'por_vencer' ? $this->ventanaDias : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function render()
    {
        $codigoTrim = trim($this->codigoSearch);

        $asesorId = $this->asesorFilter !== '' ? (int) $this->asesorFilter : null;
        $membresiaId = $this->membresiaFilter !== '' ? (int) $this->membresiaFilter : null;

        $filtros = [
            'search' => $this->search,
            'estado' => $this->estadoFilter ?: null,
            'codigo' => $codigoTrim !== '' ? $codigoTrim : null,
            'asesorId' => $asesorId,
            'vigencia' => $this->vigenciaFilter ?: null,
            'membresiaId' => $membresiaId,
            'ventanaDias' => $this->ventanaDias,
        ];

        $clientes = $this->service->search(
            $filtros['search'],
            $filtros['estado'],
            $this->perPage,
            $filtros['codigo'],
            $filtros['asesorId'],
            $filtros['vigencia'],
            $filtros['membresiaId'],
            $filtros['ventanaDias'],
        );

        $resumen = $this->service->resumenListado(
            $filtros['search'],
            $filtros['estado'],
            $filtros['codigo'],
            $filtros['asesorId'],
            $filtros['vigencia'],
            $filtros['membresiaId'],
            $filtros['ventanaDias'],
        );

        return view('livewire.clientes.cliente-live', [
            'clientes' => $clientes,
            'asesores' => $this->service->asesoresActivosParaFiltro(),
            'membresias' => $this->service->membresiasParaFiltro(),
            'resumen' => $resumen,
            'exportUrl' => route('clientes.index.exportar.excel', $this->exportQueryParams()),
        ]);
    }
}
