<?php

declare(strict_types=1);

namespace App\Livewire\BioTime;

use App\Jobs\BioTime\ReconcileBioTimeAccessForSucursal;
use App\Livewire\Concerns\FlashesToast;
use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeArea;
use App\Models\BioTime\BioTimeDepartment;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeEmployee;
use App\Models\BioTime\BioTimeMapping;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Models\BioTime\BioTimeSyncLog;
use App\Models\BioTime\BioTimeTransaction;
use App\Models\Core\Cliente;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\SucursalContext;
use Illuminate\Support\Collection;
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

    /**
     * Formularios editables por sucursal_id.
     *
     * @var array<int|string, array{
     *     area_biotime_id: string,
     *     biotime_base_url: string,
     *     poll_interval_seconds: int|string,
     *     enabled: bool
     * }>
     */
    public array $settingForms = [];

    /**
     * Sucursal IDs cuyo token se muestra en claro (solo biotime.editar).
     *
     * @var array<int, true>
     */
    public array $revealedTokenIds = [];

    public function mount(): void
    {
        Gate::authorize('biotime.ver');
        $this->loadMappings();
        $this->loadSettingForms();
    }

    public function setTab(string $tab): void
    {
        // "security" legacy redirige al modulo por sedes.
        if ($tab === 'security') {
            $tab = 'sedes';
        }

        $this->tab = in_array($tab, ['dashboard', 'sedes', 'mapping', 'history'], true) ? $tab : 'dashboard';
        $this->resetPage();

        if ($this->tab === 'sedes') {
            $this->loadSettingForms();
        }
    }

    public function saveSucursalSetting(int $sucursalId): void
    {
        Gate::authorize('biotime.editar');
        $this->assertSucursalAllowed($sucursalId);

        $form = $this->settingForms[$sucursalId] ?? [];
        $areaRaw = $form['area_biotime_id'] ?? '';
        $url = trim((string) ($form['biotime_base_url'] ?? ''));
        $poll = (int) ($form['poll_interval_seconds'] ?? 3600);
        $enabled = (bool) ($form['enabled'] ?? true);

        if ($poll < 60) {
            $this->flashToast('error', 'El intervalo de poll minimo es 60 segundos.');

            return;
        }

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        $setting->forceFill([
            'area_biotime_id' => $areaRaw === '' || $areaRaw === null ? null : (int) $areaRaw,
            'biotime_base_url' => $url !== '' ? $url : null,
            'poll_interval_seconds' => $poll,
            'enabled' => $enabled,
        ])->save();

        $this->loadSettingForms();
        $this->flashToast('success', 'Configuracion BioTime de la sede guardada.');
    }

    public function regenerateSucursalToken(int $sucursalId): void
    {
        Gate::authorize('biotime.editar');
        $this->assertSucursalAllowed($sucursalId);

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        $setting->regenerateSecret();
        $this->revealedTokenIds[$sucursalId] = true;
        $this->loadSettingForms();
        $this->flashToast('success', 'Token regenerado. Actualiza el config del puente en esa sede.');
    }

    public function revealSucursalToken(int $sucursalId): void
    {
        Gate::authorize('biotime.editar');
        $this->assertSucursalAllowed($sucursalId);
        $this->revealedTokenIds[$sucursalId] = true;
    }

    public function hideSucursalToken(int $sucursalId): void
    {
        unset($this->revealedTokenIds[$sucursalId]);
    }

    public function reconcileAccess(int $sucursalId): void
    {
        Gate::authorize('biotime.editar');
        $this->assertSucursalAllowed($sucursalId);

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        if (! $setting->enabled) {
            $this->flashToast('error', 'La sede esta deshabilitada; no se puede reconciliar acceso.');

            return;
        }

        ReconcileBioTimeAccessForSucursal::dispatch($sucursalId);
        $this->flashToast('success', 'Reconciliacion de acceso encolada para la sede.');
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
        $allowedSucursales = $this->allowedSucursales();
        $settingsBySucursal = BioTimeSucursalSetting::query()
            ->whereIn('sucursal_id', $allowedSucursales->pluck('id'))
            ->get()
            ->keyBy('sucursal_id');

        foreach ($allowedSucursales as $sucursal) {
            if (! $settingsBySucursal->has($sucursal->id)) {
                $settingsBySucursal->put($sucursal->id, BioTimeSucursalSetting::forSucursal($sucursal->id));
            }
        }

        $latestHeartbeat = $settingsBySucursal
            ->map(fn (BioTimeSucursalSetting $s) => $s->last_heartbeat_at ?? $s->last_received_at)
            ->filter()
            ->sortDesc()
            ->first();

        $isHealthy = $latestHeartbeat !== null && $latestHeartbeat->gt(now()->subMinutes(5));

        $plainTokens = [];
        foreach (array_keys($this->revealedTokenIds) as $sucursalId) {
            $plainTokens[(int) $sucursalId] = (string) ($settingsBySucursal->get((int) $sucursalId)?->webhook_secret ?? '');
        }

        $opsBySucursal = $this->opsBySucursal($allowedSucursales);

        return view('livewire.biotime.bio-time-dashboard', [
            'stats' => $this->stats(),
            'lastReceivedAt' => $latestHeartbeat,
            'isHealthy' => $isHealthy,
            'allowedSucursales' => $allowedSucursales,
            'settingsBySucursal' => $settingsBySucursal,
            'opsBySucursal' => $opsBySucursal,
            'plainTokens' => $plainTokens,
            'sucursales' => Sucursal::query()->orderBy('nombre')->get(['id', 'nombre']),
            'clientes' => $this->clientesForSelect(),
            'areas' => $this->areasForMapping(),
            'departments' => $this->departmentsForMapping(),
            'devices' => $this->devicesForMapping(),
            'employees' => $this->employeesForMapping(),
            'logs' => $this->logs(),
        ])->layout('layouts.app', ['title' => 'BioTime']);
    }

    /**
     * @param  Collection<int, Sucursal>  $sucursales
     * @return array<int, array{pending:int, failed_24h:int, heartbeat_stale:bool}>
     */
    private function opsBySucursal(Collection $sucursales): array
    {
        $ids = $sucursales->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($ids === []) {
            return [];
        }

        $since = now()->subDay();

        $pending = BioTimeAccessCommand::query()
            ->selectRaw('sucursal_id, count(*) as aggregate')
            ->whereIn('sucursal_id', $ids)
            ->whereIn('status', [
                BioTimeAccessCommand::STATUS_PENDING,
                BioTimeAccessCommand::STATUS_PROCESSING,
            ])
            ->groupBy('sucursal_id')
            ->pluck('aggregate', 'sucursal_id');

        $failed = BioTimeAccessCommand::query()
            ->selectRaw('sucursal_id, count(*) as aggregate')
            ->whereIn('sucursal_id', $ids)
            ->where('status', BioTimeAccessCommand::STATUS_FAILED)
            ->where(function ($query) use ($since): void {
                $query->where('acked_at', '>=', $since)
                    ->orWhere(function ($inner) use ($since): void {
                        $inner->whereNull('acked_at')->where('updated_at', '>=', $since);
                    });
            })
            ->groupBy('sucursal_id')
            ->pluck('aggregate', 'sucursal_id');

        $settings = BioTimeSucursalSetting::query()
            ->whereIn('sucursal_id', $ids)
            ->get()
            ->keyBy('sucursal_id');

        $ops = [];
        foreach ($ids as $sucursalId) {
            $setting = $settings->get($sucursalId);
            $heartbeat = $setting?->last_heartbeat_at;
            $ops[$sucursalId] = [
                'pending' => (int) ($pending[$sucursalId] ?? 0),
                'failed_24h' => (int) ($failed[$sucursalId] ?? 0),
                'heartbeat_stale' => $heartbeat === null || $heartbeat->lt(now()->subHours(2)),
            ];
        }

        return $ops;
    }

    private function loadSettingForms(): void
    {
        foreach ($this->allowedSucursales() as $sucursal) {
            $setting = BioTimeSucursalSetting::forSucursal($sucursal->id);
            $this->settingForms[$sucursal->id] = [
                'area_biotime_id' => $setting->area_biotime_id !== null ? (string) $setting->area_biotime_id : '',
                'biotime_base_url' => (string) ($setting->biotime_base_url ?? ''),
                'poll_interval_seconds' => (int) ($setting->poll_interval_seconds ?: 3600),
                'enabled' => (bool) $setting->enabled,
            ];
        }
    }

    private function allowedSucursales(): Collection
    {
        /** @var User $user */
        $user = auth()->user();

        return app(SucursalContext::class)->availableForUser($user);
    }

    private function assertSucursalAllowed(int $sucursalId): void
    {
        if (! $this->allowedSucursales()->contains('id', $sucursalId)) {
            abort(403);
        }
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
