<?php

declare(strict_types=1);

namespace App\Livewire\BioTime;

use App\Jobs\BioTime\ReconcileBioTimeAccessForSucursal;
use App\Livewire\Concerns\FlashesToast;
use App\Models\BioTime\BioTimeAccessCommand;
use App\Models\BioTime\BioTimeArea;
use App\Models\BioTime\BioTimeDepartment;
use App\Models\BioTime\BioTimeDevice;
use App\Models\BioTime\BioTimeMapping;
use App\Models\BioTime\BioTimeSucursalSetting;
use App\Models\BioTime\BioTimeSyncBatch;
use App\Models\BioTime\BioTimeSyncLog;
use App\Models\BioTime\BioTimeTransaction;
use App\Models\Core\Cliente;
use App\Models\System\Sucursal;
use App\Models\User;
use App\Services\BioTime\BioTimeCapacityService;
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

    public ?int $selectedSucursalId = null;

    public string $historyEntity = '';

    public string $historyStatus = '';

    public string $areaSearch = '';

    public string $departmentSearch = '';

    public string $deviceSearch = '';

    /** @var array<int|string, int|string> */
    public array $areaTargets = [];

    /** @var array<int|string, int|string> */
    public array $departmentTargets = [];

    /** @var array<int|string, int|string> */
    public array $deviceTargets = [];

    /** @var array<int|string, string> Rol entrada|salida|ambos|'' por biotime_id de device */
    public array $deviceRoles = [];

    /** @var array<int|string, bool> */
    public array $deviceAccessEnabled = [];

    /** @var array<int|string, bool> */
    public array $deviceInventoryVerified = [];

    /**
     * Formularios editables por sucursal_id.
     *
     * @var array<int|string, array{
     *     area_biotime_id: string,
     *     denied_area_biotime_id: string,
     *     company_biotime_id: string,
     *     department_biotime_id: string,
     *     biotime_base_url: string,
     *     poll_interval_seconds: int|string,
     *     enabled: bool,
     *     capacity_enforcement_enabled: bool,
     *     employee_limit: int|string
     * }>
     */
    public array $settingForms = [];

    /**
     * Sucursal IDs cuyo token se muestra en claro (solo biotime.editar).
     *
     * @var array<int, true>
     */
    public array $revealedTokenIds = [];

    public function mount(?string $tab = null): void
    {
        Gate::authorize('biotime.ver');

        $requestedTab = $tab ?? request()->query('tab');
        if (is_string($requestedTab) && $requestedTab !== '') {
            $this->setTab($requestedTab);
        }

        $allowed = $this->allowedSucursales();
        $contextId = app(SucursalContext::class)->getSucursalId();
        if ($contextId && $allowed->contains('id', $contextId)) {
            $this->selectedSucursalId = (int) $contextId;
        } else {
            $this->selectedSucursalId = $allowed->first()?->id ? (int) $allowed->first()->id : null;
        }

        $this->loadMappings();
        $this->loadSettingForms();
    }

    public function updatedSelectedSucursalId(): void
    {
        $this->resetPage();
        if ($this->selectedSucursalId) {
            $this->assertSucursalAllowed((int) $this->selectedSucursalId);
        }
        $this->loadMappings();
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
        $deniedAreaRaw = $form['denied_area_biotime_id'] ?? '';
        $companyRaw = $form['company_biotime_id'] ?? '';
        $departmentRaw = $form['department_biotime_id'] ?? '';
        $url = trim((string) ($form['biotime_base_url'] ?? ''));
        $poll = (int) ($form['poll_interval_seconds'] ?? 3600);
        $enabled = (bool) ($form['enabled'] ?? true);
        $capacityEnforcement = (bool) ($form['capacity_enforcement_enabled'] ?? false);
        $employeeLimit = (int) ($form['employee_limit'] ?? config('biotime.employee_limit_default', 500));

        if ($poll < 60) {
            $this->flashToast('error', 'El intervalo de poll minimo es 60 segundos.');

            return;
        }

        if ($employeeLimit < 1 || $employeeLimit > BioTimeCapacityService::HARD_DEVICE_LIMIT) {
            $this->flashToast('error', 'El cupo debe estar entre 1 y 500 usuarios.');

            return;
        }

        $setting = BioTimeSucursalSetting::forSucursal($sucursalId);
        $setting->forceFill([
            'area_biotime_id' => $areaRaw === '' || $areaRaw === null ? null : (int) $areaRaw,
            'denied_area_biotime_id' => $deniedAreaRaw === '' || $deniedAreaRaw === null ? null : (int) $deniedAreaRaw,
            'company_biotime_id' => $companyRaw === '' || $companyRaw === null ? null : (int) $companyRaw,
            'department_biotime_id' => $departmentRaw === '' || $departmentRaw === null ? null : (int) $departmentRaw,
            'biotime_base_url' => $url !== '' ? $url : null,
            'poll_interval_seconds' => $poll,
            'enabled' => $enabled,
            'capacity_enforcement_enabled' => $capacityEnforcement,
            'employee_limit' => $employeeLimit,
            'config_version' => ((int) $setting->config_version) + 1,
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
        $sourceSucursalId = (int) ($this->selectedSucursalId ?? 0);
        $this->assertSucursalAllowed($sourceSucursalId);
        $targets = match ($type) {
            'area' => $this->areaTargets,
            'department' => $this->departmentTargets,
            'device' => $this->deviceTargets,
            default => [],
        };
        $targetId = (int) ($targets[$biotimeId] ?? 0);

        if ($targetId <= 0) {
            BioTimeMapping::query()
                ->where('sucursal_id', $sourceSucursalId)
                ->where('mapping_type', $type)
                ->where('biotime_id', $biotimeId)
                ->delete();
            $this->flashToast('success', 'Mapeo eliminado.');

            return;
        }

        BioTimeMapping::query()->updateOrCreate(
            ['sucursal_id' => $sourceSucursalId, 'mapping_type' => $type, 'biotime_id' => $biotimeId],
            ['target_type' => 'sucursal', 'target_id' => $sourceSucursalId]
        );

        if ($type === 'device') {
            $this->persistDeviceAccessRole($biotimeId, $sourceSucursalId);
        }

        $this->flashToast('success', 'Mapeo guardado.');
    }

    public function saveDeviceAccessRole(int $biotimeId): void
    {
        Gate::authorize('biotime.editar');
        $role = (string) ($this->deviceRoles[$biotimeId] ?? '');
        $role = $role === '' ? null : $role;

        if (! BioTimeDevice::isValidAccessRole($role)) {
            $this->flashToast('error', 'Rol de terminal invalido.');

            return;
        }

        $device = BioTimeDevice::query()
            ->where('sucursal_id', (int) $this->selectedSucursalId)
            ->where('biotime_id', $biotimeId)
            ->first();
        if (! $device) {
            $this->flashToast('error', 'Dispositivo no encontrado.');

            return;
        }

        $device->forceFill([
            'access_role' => $role,
            'access_enabled' => (bool) ($this->deviceAccessEnabled[$biotimeId] ?? true),
            'inventory_verified' => (bool) ($this->deviceInventoryVerified[$biotimeId] ?? false),
        ])->save();
        BioTimeSucursalSetting::query()
            ->where('sucursal_id', (int) $this->selectedSucursalId)
            ->increment('config_version');

        $sucursalId = (int) ($this->deviceTargets[$biotimeId] ?? 0);
        $warning = $this->deviceRoleCountWarning($sucursalId);
        $this->flashToast(
            'success',
            $warning ?? 'Rol de terminal guardado.'
        );
    }

    private function persistDeviceAccessRole(int $biotimeId, int $sucursalId): void
    {
        $role = (string) ($this->deviceRoles[$biotimeId] ?? '');
        $role = $role === '' ? null : $role;
        if (! BioTimeDevice::isValidAccessRole($role)) {
            return;
        }

        BioTimeDevice::query()
            ->where('sucursal_id', $sucursalId)
            ->where('biotime_id', $biotimeId)
            ->update(['access_role' => $role]);

        $warning = $this->deviceRoleCountWarning($sucursalId);
        if ($warning !== null) {
            $this->flashToast('success', 'Mapeo guardado. '.$warning);
        }
    }

    private function deviceRoleCountWarning(int $sucursalId): ?string
    {
        if ($sucursalId <= 0) {
            return null;
        }

        $withRole = BioTimeDevice::query()
            ->where('sucursal_id', $sucursalId)
            ->whereNotNull('access_role')
            ->where('access_role', '!=', '')
            ->count();

        if ($withRole > 2) {
            return "Atencion: la sede tiene {$withRole} terminales con rol (recomendado maximo 2).";
        }

        return null;
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
        $capacityBySucursal = $this->capacityBySucursal($allowedSucursales);
        $selectedId = $this->resolvedSelectedSucursalId($allowedSucursales);
        $selectedSetting = $selectedId ? $settingsBySucursal->get($selectedId) : null;
        $selectedHeartbeat = $selectedSetting?->last_heartbeat_at ?? $selectedSetting?->last_received_at;
        $selectedHealthy = $selectedHeartbeat !== null && $selectedHeartbeat->gt(now()->subMinutes(5));

        return view('livewire.biotime.bio-time-dashboard', [
            'stats' => $this->statsForSucursal($selectedId),
            'lastReceivedAt' => $selectedHeartbeat ?? $latestHeartbeat,
            'isHealthy' => $selectedId ? $selectedHealthy : $isHealthy,
            'selectedSucursal' => $selectedId ? $allowedSucursales->firstWhere('id', $selectedId) : null,
            'selectedOps' => $selectedId ? ($opsBySucursal[$selectedId] ?? null) : null,
            'selectedCapacity' => $selectedId ? ($capacityBySucursal[$selectedId] ?? null) : null,
            'selectedSetting' => $selectedSetting,
            'allowedSucursales' => $allowedSucursales,
            'settingsBySucursal' => $settingsBySucursal,
            'opsBySucursal' => $opsBySucursal,
            'capacityBySucursal' => $capacityBySucursal,
            'plainTokens' => $plainTokens,
            'sucursales' => Sucursal::query()->orderBy('nombre')->get(['id', 'nombre']),
            'areas' => $this->areasForMapping($selectedId),
            'departments' => $this->departmentsForMapping($selectedId),
            'devices' => $this->devicesForMapping($selectedId),
            'logs' => $this->logs($selectedId),
            'accessCommands' => $this->accessCommands($selectedId),
        ])->layout('layouts.app', ['title' => 'BioTime']);
    }

    private function resolvedSelectedSucursalId(Collection $allowed): ?int
    {
        if ($this->selectedSucursalId && $allowed->contains('id', $this->selectedSucursalId)) {
            return (int) $this->selectedSucursalId;
        }

        $first = $allowed->first();

        return $first ? (int) $first->id : null;
    }

    /**
     * @param  Collection<int, Sucursal>  $sucursales
     * @return array<int, array{occupied:int, limit:int, percent:float, alert:bool}>
     */
    private function capacityBySucursal(Collection $sucursales): array
    {
        $capacity = app(BioTimeCapacityService::class);
        $out = [];
        foreach ($sucursales as $sucursal) {
            $id = (int) $sucursal->id;
            $out[$id] = [
                'occupied' => $capacity->occupiedForSucursal($id),
                'limit' => $capacity->limitForSucursal($id),
                'percent' => $capacity->usagePercent($id),
                'alert' => $capacity->isAlertThreshold($id),
                'roster' => $capacity->rosterCapacity($id),
            ];
        }

        return $out;
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
                'denied_area_biotime_id' => $setting->denied_area_biotime_id !== null ? (string) $setting->denied_area_biotime_id : '',
                'company_biotime_id' => $setting->company_biotime_id !== null ? (string) $setting->company_biotime_id : '',
                'department_biotime_id' => $setting->department_biotime_id !== null ? (string) $setting->department_biotime_id : '',
                'biotime_base_url' => (string) ($setting->biotime_base_url ?? ''),
                'poll_interval_seconds' => (int) ($setting->poll_interval_seconds ?: 3600),
                'enabled' => (bool) $setting->enabled,
                'capacity_enforcement_enabled' => (bool) $setting->capacity_enforcement_enabled,
                'employee_limit' => (int) ($setting->employee_limit ?: config('biotime.employee_limit_default', 500)),
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
        $this->areaTargets = [];
        $this->departmentTargets = [];
        $this->deviceTargets = [];
        $this->deviceRoles = [];
        $this->deviceAccessEnabled = [];
        $this->deviceInventoryVerified = [];
        $sucursalId = (int) ($this->selectedSucursalId ?? 0);

        BioTimeMapping::query()
            ->where('sucursal_id', $sucursalId)
            ->get()
            ->each(function (BioTimeMapping $mapping): void {
                match ($mapping->mapping_type) {
                    'area' => $this->areaTargets[$mapping->biotime_id] = $mapping->target_id,
                    'department' => $this->departmentTargets[$mapping->biotime_id] = $mapping->target_id,
                    'device' => $this->deviceTargets[$mapping->biotime_id] = $mapping->target_id,
                    default => null,
                };
            });

        BioTimeDevice::query()
            ->where('sucursal_id', $sucursalId)
            ->whereNotNull('biotime_id')
            ->get(['biotime_id', 'access_role', 'access_enabled', 'inventory_verified'])
            ->each(function (BioTimeDevice $device): void {
                $this->deviceRoles[$device->biotime_id] = (string) ($device->access_role ?? '');
                $this->deviceAccessEnabled[$device->biotime_id] = (bool) $device->access_enabled;
                $this->deviceInventoryVerified[$device->biotime_id] = (bool) $device->inventory_verified;
            });
    }

    private function statsForSucursal(?int $sucursalId): array
    {
        if (! $sucursalId) {
            return [
                'clientes' => 0,
                'departments' => 0,
                'areasMapped' => 0,
                'devicesOnline' => 0,
                'todayPunches' => 0,
                'batches' => 0,
            ];
        }

        $mappedAreaIds = BioTimeMapping::query()
            ->where('mapping_type', 'area')
            ->where('sucursal_id', $sucursalId)
            ->pluck('biotime_id');
        $mappedDeviceIds = BioTimeMapping::query()
            ->where('mapping_type', 'device')
            ->where('sucursal_id', $sucursalId)
            ->pluck('biotime_id');
        $mappedDeptIds = BioTimeMapping::query()
            ->where('mapping_type', 'department')
            ->where('sucursal_id', $sucursalId)
            ->pluck('biotime_id');

        $clienteIds = Cliente::query()->where('sucursal_id', $sucursalId)->pluck('id');

        return [
            'clientes' => Cliente::query()
                ->where('sucursal_id', $sucursalId)
                ->whereNotNull('biotime_id')
                ->count(),
            'departments' => $mappedDeptIds->count(),
            'areasMapped' => $mappedAreaIds->count(),
            'devicesOnline' => BioTimeDevice::query()
                ->where('sucursal_id', $sucursalId)
                ->where('access_enabled', true)
                ->where(function ($query): void {
                    $query->whereIn('state', [1, 2])->orWhere('last_activity', '>=', now()->subMinutes(10));
                })
                ->count(),
            'todayPunches' => BioTimeTransaction::query()
                ->where('sucursal_id', $sucursalId)
                ->whereDate('punch_time', today())
                ->whereIn('cliente_id', $clienteIds)
                ->count(),
            'batches' => BioTimeSyncBatch::query()->where('sucursal_id', $sucursalId)->count(),
        ];
    }

    private function areasForMapping(?int $sucursalId = null)
    {
        $query = BioTimeArea::query()
            ->where('sucursal_id', $sucursalId)
            ->when($this->areaSearch !== '', fn ($q) => $q->where(function ($inner): void {
                $inner->where('area_name', 'like', '%'.$this->areaSearch.'%')
                    ->orWhere('area_code', 'like', '%'.$this->areaSearch.'%');
            }));

        if ($sucursalId) {
            $mappedToSede = BioTimeMapping::query()
                ->where('mapping_type', 'area')
                ->where('sucursal_id', $sucursalId)
                ->pluck('biotime_id');
            $mappedAny = BioTimeMapping::query()
                ->where('mapping_type', 'area')
                ->pluck('biotime_id');

            $query->where(function ($q) use ($mappedToSede, $mappedAny): void {
                $q->whereIn('biotime_id', $mappedToSede)
                    ->orWhereNotIn('biotime_id', $mappedAny);
            });
        }

        return $query->orderBy('area_name')->limit(40)->get();
    }

    private function departmentsForMapping(?int $sucursalId = null)
    {
        $query = BioTimeDepartment::query()
            ->where('sucursal_id', $sucursalId)
            ->when($this->departmentSearch !== '', fn ($q) => $q->where(function ($inner): void {
                $inner->where('dept_name', 'like', '%'.$this->departmentSearch.'%')
                    ->orWhere('dept_code', 'like', '%'.$this->departmentSearch.'%');
            }));

        if ($sucursalId) {
            $mappedToSede = BioTimeMapping::query()
                ->where('mapping_type', 'department')
                ->where('sucursal_id', $sucursalId)
                ->pluck('biotime_id');
            $mappedAny = BioTimeMapping::query()
                ->where('mapping_type', 'department')
                ->pluck('biotime_id');

            $query->where(function ($q) use ($mappedToSede, $mappedAny): void {
                $q->whereIn('biotime_id', $mappedToSede)
                    ->orWhereNotIn('biotime_id', $mappedAny);
            });
        }

        return $query->orderBy('dept_name')->limit(40)->get();
    }

    private function devicesForMapping(?int $sucursalId = null)
    {
        $query = BioTimeDevice::query()
            ->where('sucursal_id', $sucursalId)
            ->when($this->deviceSearch !== '', fn ($q) => $q->where(function ($inner): void {
                $inner->where('alias', 'like', '%'.$this->deviceSearch.'%')
                    ->orWhere('serial_number', 'like', '%'.$this->deviceSearch.'%');
            }));

        if ($sucursalId) {
            $mappedToSede = BioTimeMapping::query()
                ->where('mapping_type', 'device')
                ->where('sucursal_id', $sucursalId)
                ->pluck('biotime_id');
            $mappedAny = BioTimeMapping::query()
                ->where('mapping_type', 'device')
                ->pluck('biotime_id');

            $query->where(function ($q) use ($mappedToSede, $mappedAny): void {
                $q->whereIn('biotime_id', $mappedToSede)
                    ->orWhereNotIn('biotime_id', $mappedAny);
            });
        }

        return $query->orderBy('alias')->limit(40)->get();
    }

    private function logs(?int $sucursalId = null)
    {
        return BioTimeSyncLog::query()
            ->when($sucursalId, fn ($query) => $query->where('sucursal_id', $sucursalId))
            ->when($sucursalId, function ($query) use ($sucursalId): void {
                $query->whereIn(
                    'batch_id',
                    BioTimeSyncBatch::query()->where('sucursal_id', $sucursalId)->select('batch_id')
                );
            })
            ->when($this->historyEntity !== '', fn ($query) => $query->where('entity', $this->historyEntity))
            ->when($this->historyStatus !== '', fn ($query) => $query->where('status', $this->historyStatus))
            ->latest('processed_at')
            ->paginate(15, pageName: 'syncPage');
    }

    private function accessCommands(?int $sucursalId = null)
    {
        return BioTimeAccessCommand::query()
            ->when($sucursalId, fn ($q) => $q->where('sucursal_id', $sucursalId))
            ->with('cliente:id,codigo,nombres,apellidos')
            ->latest('id')
            ->paginate(15, pageName: 'cmdPage');
    }
}
