<?php

declare(strict_types=1);

namespace App\Livewire\BioTime;

use App\Livewire\Concerns\FlashesToast;
use App\Models\BioTime\BioTimeArea;
use App\Models\BioTime\BioTimeDepartment;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeMapping;
use App\Models\BioTime\BioTimeSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Models\BioTime\BioTimeSyncLog;
use App\Models\BioTime\BioTimeTransaction;
use App\Models\Core\Cliente;
use App\Models\System\Sucursal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class BioTimeDashboard extends Component
{
    use FlashesToast;
    use WithPagination;

    public string $tab = 'dashboard';

    public string $historyEntity = '';

    public string $historyStatus = '';

    public string $areaSearch = '';

    public string $departmentSearch = '';

    public string $deviceSearch = '';

    public string $employeeSearch = '';

    /** @var array<int|string, int|string> */
    public array $areaTargets = [];

    /** @var array<int|string, int|string> */
    public array $departmentTargets = [];

    /** @var array<int|string, int|string> */
    public array $deviceTargets = [];

    /** @var array<int|string, int|string> */
    public array $employeeTargets = [];

    public function mount(): void
    {
        Gate::authorize('biotime.ver');
        $this->loadMappings();
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, ['dashboard', 'security', 'mapping', 'history'], true) ? $tab : 'dashboard';
        $this->resetPage();
    }

    public function regenerateToken(): void
    {
        Gate::authorize('biotime.editar');
        BioTimeSetting::current()->regenerateSecret();
        $this->flashToast('success', 'Token BioTime regenerado.');
    }

    public function saveSucursalMapping(string $type, int $biotimeId): void
    {
        Gate::authorize('biotime.editar');
        $targets = match ($type) {
            'area' => $this->areaTargets,
            'department' => $this->departmentTargets,
            'device' => $this->deviceTargets,
            default => [],
        };
        $targetId = (int) ($targets[$biotimeId] ?? 0);

        if ($targetId <= 0) {
            BioTimeMapping::query()->where('mapping_type', $type)->where('biotime_id', $biotimeId)->delete();
            $this->flashToast('success', 'Mapeo eliminado.');

            return;
        }

        BioTimeMapping::query()->updateOrCreate(
            ['mapping_type' => $type, 'biotime_id' => $biotimeId],
            ['target_type' => 'sucursal', 'target_id' => $targetId, 'sucursal_id' => $targetId]
        );

        $this->flashToast('success', 'Mapeo guardado.');
    }

    public function saveEmployeeMapping(int $biotimeId): void
    {
        Gate::authorize('biotime.editar');
        $clienteId = (int) ($this->employeeTargets[$biotimeId] ?? 0);

        if ($clienteId <= 0) {
            BioTimeMapping::query()->where('mapping_type', 'employee')->where('biotime_id', $biotimeId)->delete();
            $this->flashToast('success', 'Mapeo eliminado.');

            return;
        }

        $cliente = Cliente::query()->findOrFail($clienteId);
        BioTimeMapping::query()->updateOrCreate(
            ['mapping_type' => 'employee', 'biotime_id' => $biotimeId],
            ['target_type' => 'cliente', 'target_id' => $cliente->id, 'sucursal_id' => $cliente->sucursal_id]
        );

        BioTimeEmployee::query()->where('biotime_id', $biotimeId)->update(['cliente_id' => $cliente->id]);
        $cliente->forceFill(['biotime_id' => $biotimeId])->save();

        $this->flashToast('success', 'Cliente enlazado a BioTime.');
    }

    public function updatingHistoryEntity(): void
    {
        $this->resetPage();
    }

    public function updatingHistoryStatus(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $secret = BioTimeSetting::activeSecret() ?: '';
        $lastReceived = Cache::get('biotime:last_received_at') ?? BioTimeSetting::current()->last_received_at?->toIso8601String();
        $lastReceivedAt = $lastReceived ? Carbon::parse($lastReceived) : null;

        return view('livewire.biotime.bio-time-dashboard', [
            'stats' => $this->stats(),
            'secret' => $secret,
            'lastReceivedAt' => $lastReceivedAt,
            'isHealthy' => $lastReceivedAt !== null && $lastReceivedAt->gt(now()->subMinutes(5)),
            'sucursales' => Sucursal::query()->orderBy('nombre')->get(['id', 'nombre']),
            'clientes' => $this->clientesForSelect(),
            'areas' => $this->areasForMapping(),
            'departments' => $this->departmentsForMapping(),
            'devices' => $this->devicesForMapping(),
            'employees' => $this->employeesForMapping(),
            'logs' => $this->logs(),
        ])->layout('layouts.app', ['title' => 'BioTime']);
    }

    private function loadMappings(): void
    {
        BioTimeMapping::query()->get()->each(function (BioTimeMapping $mapping): void {
            match ($mapping->mapping_type) {
                'area' => $this->areaTargets[$mapping->biotime_id] = $mapping->target_id,
                'department' => $this->departmentTargets[$mapping->biotime_id] = $mapping->target_id,
                'device' => $this->deviceTargets[$mapping->biotime_id] = $mapping->target_id,
                'employee' => $this->employeeTargets[$mapping->biotime_id] = $mapping->target_id,
                default => null,
            };
        });
    }

    private function stats(): array
    {
        return [
            'clientes' => Cliente::query()->where('estado_cliente', 'activo')->whereNotNull('biotime_id')->count(),
            'departments' => BioTimeDepartment::query()->count(),
            'areasMapped' => BioTimeMapping::query()->where('mapping_type', 'area')->count(),
            'devicesOnline' => BioTimeDevice::query()
                ->where(function ($query): void {
                    $query->whereIn('state', [1, 2])->orWhere('last_activity', '>=', now()->subMinutes(10));
                })
                ->count(),
            'todayPunches' => BioTimeTransaction::query()->whereDate('punch_time', today())->count(),
            'batches' => BioTimeSyncBatch::query()->count(),
        ];
    }

    private function areasForMapping()
    {
        return BioTimeArea::query()
            ->when($this->areaSearch !== '', fn ($query) => $query->where('area_name', 'like', '%'.$this->areaSearch.'%')->orWhere('area_code', 'like', '%'.$this->areaSearch.'%'))
            ->orderBy('area_name')
            ->limit(25)
            ->get();
    }

    private function departmentsForMapping()
    {
        return BioTimeDepartment::query()
            ->when($this->departmentSearch !== '', fn ($query) => $query->where('dept_name', 'like', '%'.$this->departmentSearch.'%')->orWhere('dept_code', 'like', '%'.$this->departmentSearch.'%'))
            ->orderBy('dept_name')
            ->limit(25)
            ->get();
    }

    private function devicesForMapping()
    {
        return BioTimeDevice::query()
            ->when($this->deviceSearch !== '', fn ($query) => $query->where('alias', 'like', '%'.$this->deviceSearch.'%')->orWhere('serial_number', 'like', '%'.$this->deviceSearch.'%'))
            ->orderBy('alias')
            ->limit(25)
            ->get();
    }

    private function employeesForMapping()
    {
        return BioTimeEmployee::query()
            ->when($this->employeeSearch !== '', function ($query): void {
                $query->where('emp_code', 'like', '%'.$this->employeeSearch.'%')
                    ->orWhere('first_name', 'like', '%'.$this->employeeSearch.'%')
                    ->orWhere('last_name', 'like', '%'.$this->employeeSearch.'%');
            })
            ->orderBy('emp_code')
            ->limit(25)
            ->get();
    }

    private function clientesForSelect()
    {
        return Cliente::query()
            ->select(['id', 'codigo', 'nombres', 'apellidos', 'sucursal_id'])
            ->when($this->employeeSearch !== '', function ($query): void {
                $query->where('codigo', 'like', '%'.$this->employeeSearch.'%')
                    ->orWhere('nombres', 'like', '%'.$this->employeeSearch.'%')
                    ->orWhere('apellidos', 'like', '%'.$this->employeeSearch.'%');
            })
            ->orderBy('codigo')
            ->limit(50)
            ->get();
    }

    private function logs()
    {
        return BioTimeSyncLog::query()
            ->when($this->historyEntity !== '', fn ($query) => $query->where('entity', $this->historyEntity))
            ->when($this->historyStatus !== '', fn ($query) => $query->where('status', $this->historyStatus))
            ->latest('processed_at')
            ->paginate(15);
    }
}
