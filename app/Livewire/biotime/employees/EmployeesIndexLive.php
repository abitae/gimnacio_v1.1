<?php

namespace App\Livewire\biotime\employees;

use App\Models\Core\Cliente;
use App\Services\BiotimeApiClient;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class EmployeesIndexLive extends Component
{
    use WithPagination;

    public $tab = 'activos';

    public $searchActivos = '';

    public $searchInactivos = '';

    public $searchSuspendidos = '';

    public $perPage = 15;

    public $message = '';

    public $messageSuccess = false;

    public $confirmSuspendId = null;

    /** Si true, se muestra el modal de "Suspender todos los inactivos". */
    public $showSuspendMasivoModal = false;

    protected $queryString = [
        'tab' => ['except' => 'activos'],
        'searchActivos' => ['except' => ''],
        'searchInactivos' => ['except' => ''],
        'searchSuspendidos' => ['except' => ''],
    ];

    protected BiotimeApiClient $client;

    protected $paginationTheme = 'tailwind';

    public function boot(BiotimeApiClient $client)
    {
        $this->client = $client;
    }

    public function mount()
    {
        $this->authorize('biotime.view');
    }

    public function switchTab(string $tab)
    {
        $this->tab = $tab;
        $this->resetPage();
    }

    public function updatedSearchActivos()
    {
        $this->resetPage('page_activos');
    }

    public function updatedSearchInactivos()
    {
        $this->resetPage('page_inactivos');
    }

    public function updatedSearchSuspendidos()
    {
        $this->resetPage('page_suspendidos');
    }

    public function updatedPerPage()
    {
        $this->resetPage();
    }

    protected function applySearch($query, string $search): void
    {
        if ($search === '') {
            return;
        }
        $term = '%' . $search . '%';
        $query->where(function ($q) use ($term) {
            $q->where('nombres', 'like', $term)
                ->orWhere('apellidos', 'like', $term)
                ->orWhere('numero_documento', 'like', $term);
        });
    }

    public function getClientesActivosPaginatorProperty(): LengthAwarePaginator
    {
        $query = Cliente::query()
            ->where('estado_cliente', 'activo')
            ->select(['id', 'nombres', 'apellidos', 'numero_documento', 'estado_cliente', 'biotime_state', 'biotime_update']);
        $this->applySearch($query, $this->searchActivos);
        $query->orderBy('nombres');

        return $query->paginate($this->perPage, ['*'], 'page_activos');
    }

    public function getClientesInactivosPaginatorProperty(): LengthAwarePaginator
    {
        $query = Cliente::query()
            ->where('estado_cliente', 'inactivo')
            ->select(['id', 'nombres', 'apellidos', 'numero_documento', 'estado_cliente', 'biotime_state']);
        $this->applySearch($query, $this->searchInactivos);
        $query->orderBy('nombres');

        return $query->paginate($this->perPage, ['*'], 'page_inactivos');
    }

    public function getClientesSuspendidosPaginatorProperty(): LengthAwarePaginator
    {
        $query = Cliente::query()
            ->where('estado_cliente', 'suspendido')
            ->select(['id', 'nombres', 'apellidos', 'numero_documento', 'estado_cliente', 'biotime_state']);
        $this->applySearch($query, $this->searchSuspendidos);
        $query->orderBy('nombres');

        return $query->paginate($this->perPage, ['*'], 'page_suspendidos');
    }

    /**
     * Inactivos (todos, con filtro actual) para suspender masivo.
     */
    public function getClientesInactivosAllProperty()
    {
        $query = Cliente::query()->where('estado_cliente', 'inactivo');
        $this->applySearch($query, $this->searchInactivos);
        $query->orderBy('nombres');

        return $query->get(['id', 'nombres', 'apellidos']);
    }

    /**
     * Suspender un cliente inactivo: eliminar de BioTime (si existe) y marcar suspendido en la app.
     */
    public function suspendCliente(int $clienteId)
    {
        $this->authorize('biotime.delete');
        $this->message = '';
        $this->messageSuccess = false;
        $cliente = Cliente::where('estado_cliente', 'inactivo')->find($clienteId);
        if (! $cliente) {
            $this->message = 'Cliente no encontrado o no está inactivo.';
            return;
        }
        try {
            $this->deleteFromBiotimeIfExists($clienteId);
            $cliente->update([
                'estado_cliente' => 'suspendido',
                'biotime_state' => false,
                'biotime_update' => false,
            ]);
            $this->message = $cliente->nombres . ' ' . $cliente->apellidos . ' suspendido. Eliminado de BioTime si existía.';
            $this->messageSuccess = true;
        } catch (\Throwable $e) {
            $this->message = 'Error: ' . $e->getMessage();
        }
        $this->confirmSuspendId = null;
    }

    /**
     * Suspender todos los clientes inactivos (filtro actual).
     */
    public function suspendClientesMasivo()
    {
        $this->authorize('biotime.delete');
        $this->message = '';
        $this->messageSuccess = false;
        $clientes = $this->clientesInactivosAll;
        if ($clientes->isEmpty()) {
            $this->message = 'No hay clientes inactivos para suspender.';
            $this->showSuspendMasivoModal = false;
            return;
        }
        set_time_limit(120);
        $count = 0;
        $errors = [];
        $skipped = 0;
        foreach ($clientes as $cliente) {
            $fresh = Cliente::find($cliente->id);
            if (! $fresh || $fresh->estado_cliente !== 'inactivo') {
                $skipped++;
                continue;
            }
            try {
                $this->deleteFromBiotimeIfExists($fresh->id);
                $fresh->update([
                    'estado_cliente' => 'suspendido',
                    'biotime_state' => false,
                    'biotime_update' => false,
                ]);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = $fresh->nombres . ' ' . $fresh->apellidos . ': ' . $e->getMessage();
            }
        }
        $this->message = "Suspendidos: {$count} de " . $clientes->count() . '.';
        if ($skipped > 0) {
            $this->message .= " Omitidos (activos): {$skipped}.";
        }
        if (count($errors) > 0) {
            $this->message .= ' Errores: ' . implode('; ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $this->message .= '... (+' . (count($errors) - 3) . ' más)';
            }
        }
        $this->messageSuccess = $count > 0;
        $this->showSuspendMasivoModal = false;
    }

    /**
     * Elimina el empleado de BioTime si existe.
     * No hace nada si el cliente está activo: no se puede suspender en BioTime un cliente activo.
     */
    protected function deleteFromBiotimeIfExists(int $clienteId): void
    {
        $cliente = Cliente::find($clienteId);
        if ($cliente && $cliente->estado_cliente === 'activo') {
            return;
        }
        try {
            $this->client->deleteEmployee($clienteId);
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '404') || str_contains($msg, 'No encontrado') || str_contains($msg, 'not found')) {
                return;
            }
            throw $e;
        }
    }

    public function confirmSuspend(int $id)
    {
        $this->confirmSuspendId = $id;
    }

    public function cancelSuspend()
    {
        $this->confirmSuspendId = null;
        $this->showSuspendMasivoModal = false;
    }

    /** Abre el modal de confirmación para suspender todos los inactivos. */
    public function confirmSuspendMasivo()
    {
        $this->showSuspendMasivoModal = true;
    }

    public function render()
    {
        return view('livewire.biotime.employees.employees-index-live');
    }
}
