<?php

namespace App\Livewire\biotime;

use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use App\Models\Integration\BiotimeAccessLog;
use App\Models\Integration\IntegrationErrorLog;
use App\Services\BiotimeApiClient;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class BiotimeSyncLive extends Component
{
    public $tab = 'employees';

    public $employees = [];

    public $employeesPage = 1;

    public $employeesTotal = 0;

    public $syncStartDate = '';

    public $syncEndDate = '';

    public $syncResult = null;

    public $syncMessage = '';

    public $uploadDepartmentId = '';

    public $uploadAreaId = '';

    public $departmentsList = [];

    public $areasList = [];

    public $uploadResult = null;

    public $uploadMessage = '';

    protected BiotimeApiClient $client;

    public function boot(BiotimeApiClient $client)
    {
        $this->client = $client;
    }

    public function mount()
    {
        $this->authorize('biotime.view');
        $this->syncStartDate = now()->subDays(7)->format('Y-m-d');
        $this->syncEndDate = now()->format('Y-m-d');
        $this->loadEmployees();
    }

    public function switchTab(string $tab)
    {
        $this->tab = $tab;
        if ($tab === 'employees') {
            $this->loadEmployees();
        }
        if ($tab === 'upload') {
            $this->loadDepartments();
            $this->loadAreas();
        }
    }

    public function loadDepartments()
    {
        try {
            $response = $this->client->listDepartments(['page_size' => 200]);
            $this->departmentsList = $response['data'] ?? [];
        } catch (\Throwable $e) {
            $this->departmentsList = [];
        }
    }

    public function loadAreas()
    {
        try {
            $response = $this->client->listAreas(['page_size' => 200]);
            $this->areasList = $response['data'] ?? [];
        } catch (\Throwable $e) {
            $this->areasList = [];
        }
    }

    /**
     * Obtiene el company id del departamento (requerido por la API al crear empleados).
     */
    protected function resolveCompanyIdForDepartment(int $departmentId): ?int
    {
        foreach ($this->departmentsList as $dept) {
            if ((int) ($dept['id'] ?? 0) === $departmentId) {
                $company = $dept['company'] ?? null;
                if (is_array($company) && ! empty($company['id'])) {
                    return (int) $company['id'];
                }
                break;
            }
        }
        try {
            $dept = $this->client->getDepartment($departmentId);
            $company = $dept['company'] ?? null;
            if (is_array($company) && ! empty($company['id'])) {
                return (int) $company['id'];
            }
        } catch (\Throwable $e) {
            return null;
        }
        return null;
    }

    public function loadEmployees()
    {
        try {
            $response = $this->client->listEmployees([
                'page' => $this->employeesPage,
                'page_size' => 20,
            ]);
            $this->employees = $response['data'] ?? [];
            $this->employeesTotal = (int) ($response['count'] ?? 0);
        } catch (\Throwable $e) {
            $this->syncMessage = 'Error al cargar empleados: ' . $e->getMessage();
            $this->employees = [];
        }
    }

    public function syncTransactions()
    {
        $this->authorize('biotime.create');
        $this->validate([
            'syncStartDate' => ['required', 'date'],
            'syncEndDate' => ['required', 'date', 'after_or_equal:syncStartDate'],
        ]);

        set_time_limit(300);

        $this->syncResult = null;
        $this->syncMessage = '';

        try {
            $start = Carbon::parse($this->syncStartDate)->startOfDay()->format('Y-m-d H:i:s');
            $end = Carbon::parse($this->syncEndDate)->endOfDay()->format('Y-m-d H:i:s');

            $response = $this->client->listTransactions([
                'start_time' => $start,
                'end_time' => $end,
                'page_size' => 100,
            ]);

            $data = $response['data'] ?? [];
            $createdLogs = 0;
            $createdAsistencias = 0;
            $skipped = 0;

            foreach ($data as $tx) {
                $empCode = $tx['emp_code'] ?? null;
                $punchTime = $tx['punch_time'] ?? null;
                $terminalSn = $tx['terminal_sn'] ?? null;
                $punchStateDisplay = $tx['punch_state_display'] ?? '';
                $txId = $tx['id'] ?? null;

                if (!$punchTime) {
                    $skipped++;
                    continue;
                }

                $cliente = ($empCode !== null && $empCode !== '') && is_numeric($empCode) ? Cliente::find((int) $empCode) : null;
                $eventType = (stripos($punchStateDisplay, 'Check In') !== false || stripos($punchStateDisplay, 'In') !== false) ? 'entry' : 'exit';

                $exists = BiotimeAccessLog::where('biotime_user_id', $empCode ?? '')
                    ->where('event_time', Carbon::parse($punchTime))
                    ->where('device_id', $terminalSn)
                    ->exists();
                if ($exists) {
                    $skipped++;
                    continue;
                }

                BiotimeAccessLog::create([
                    'biotime_user_id' => $empCode ?? '',
                    'cliente_id' => $cliente?->id,
                    'device_id' => $terminalSn,
                    'event_time' => Carbon::parse($punchTime),
                    'event_type' => $eventType,
                    'result' => 'success',
                    'raw_payload' => $tx,
                ]);
                $createdLogs++;

                if ($cliente && $eventType === 'entry') {
                    Asistencia::create([
                        'cliente_id' => $cliente->id,
                        'cliente_membresia_id' => null,
                        'cliente_matricula_id' => null,
                        'fecha_hora_ingreso' => Carbon::parse($punchTime),
                        'fecha_hora_salida' => null,
                        'origen' => 'biotime',
                        'valido_por_membresia' => true,
                        'registrada_por' => Auth::user()?->id,
                    ]);
                    $createdAsistencias++;
                }
            }

            $this->syncResult = true;
            $this->syncMessage = "Transacciones sincronizadas: {$createdLogs} registros en BiotimeAccessLog, {$createdAsistencias} asistencias creadas." . ($skipped ? " Omitidos: {$skipped}." : '');
        } catch (\Throwable $e) {
            $this->syncResult = false;
            $this->syncMessage = $e->getMessage();
            try {
                IntegrationErrorLog::create([
                    'source' => 'biotime',
                    'payload' => ['action' => 'sync_transactions', 'start' => $this->syncStartDate, 'end' => $this->syncEndDate],
                    'error_message' => $e->getMessage(),
                ]);
            } catch (\Throwable $_) {
            }
        }
    }

    /**
     * Get clientes a sincronizar con BioTime: estado_cliente = activo y (biotime_state = false o biotime_update = true).
     */
    public function getClientesActivosProperty()
    {
        return Cliente::query()
            ->where('estado_cliente', 'activo')
            ->where(function ($q) {
                $q->where('biotime_state', false)->orWhere('biotime_update', true);
            })
            ->orderBy('nombres')
            ->get(['id', 'nombres', 'apellidos', 'numero_documento', 'biotime_state', 'biotime_update']);
    }

    /**
     * Sincroniza todos los clientes activos con BioTime.
     * - biotime_state false: crear en BioTime y poner biotime_state true.
     * - biotime_state true y biotime_update true: actualizar en BioTime y poner biotime_update false.
     * - biotime_state true y biotime_update false: omitir (sin cambios pendientes).
     */
    public function syncClientesToBiotime()
    {
        $this->authorize('biotime.create');
        $this->validate([
            'uploadDepartmentId' => ['required', 'integer', 'min:1'],
            'uploadAreaId' => ['required', 'integer', 'min:1'],
        ], [], [
            'uploadDepartmentId' => 'departamento',
            'uploadAreaId' => 'área',
        ]);

        set_time_limit(300);

        $this->uploadResult = null;
        $this->uploadMessage = '';

        $clientes = $this->getClientesActivosProperty();
        if ($clientes->isEmpty()) {
            $this->uploadResult = false;
            $this->uploadMessage = 'No hay clientes activos para sincronizar.';
            return;
        }

        $companyId = $this->resolveCompanyIdForDepartment((int) $this->uploadDepartmentId);
        if ($companyId === null || $companyId <= 0) {
            $this->uploadResult = false;
            $this->uploadMessage = 'No se pudo obtener la empresa del departamento seleccionado.';
            return;
        }

        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            foreach ($clientes as $cliente) {
                $clienteId = (int) $cliente->id;
                $empCode = (string) $cliente->id;
                $synced = (bool) $cliente->biotime_state;
                $pendingUpdate = (bool) $cliente->biotime_update;

                if (! $synced) {
                    try {
                        $this->client->createEmployee([
                            'emp_code' => $empCode,
                            'department' => (int) $this->uploadDepartmentId,
                            'company' => $companyId,
                            'area' => [(int) $this->uploadAreaId],
                            'first_name' => $cliente->nombres ?? '',
                            'last_name' => $cliente->apellidos ?? '',
                        ]);
                        $cliente->update(['biotime_state' => true]);
                        $created++;
                    } catch (\Throwable $e) {
                        $errors[] = $cliente->nombres . ' ' . $cliente->apellidos . ': ' . $e->getMessage();
                    }
                    continue;
                }

                if ($synced && $pendingUpdate) {
                    try {
                        $emp = $this->client->getEmployee($clienteId);
                        $dept = $emp['department'] ?? [];
                        $departmentId = is_array($dept) ? (int) ($dept['id'] ?? 0) : (int) $dept;
                        $companyIdEmp = null;
                        if (is_array($dept) && ! empty($dept['company']['id'])) {
                            $companyIdEmp = (int) $dept['company']['id'];
                        }
                        if ($companyIdEmp === null || $companyIdEmp <= 0) {
                            $companyIdEmp = $this->resolveCompanyIdForDepartment($departmentId) ?: $companyId;
                        }
                        $areaList = $emp['area'] ?? [];
                        $areaIds = is_array($areaList) ? array_values(array_filter(array_map('intval', array_column($areaList, 'id')))) : [];
                        if (empty($areaIds)) {
                            $areaIds = [1];
                        }

                        $this->client->updateEmployee($clienteId, [
                            'emp_code' => $empCode,
                            'department' => $departmentId,
                            'company' => $companyIdEmp ?: $companyId,
                            'area' => $areaIds,
                            'first_name' => $cliente->nombres ?? '',
                            'last_name' => $cliente->apellidos ?? '',
                            'hire_date' => now()->format('Y-m-d'),
                            'app_status' => 1,
                        ]);
                        $cliente->update(['biotime_update' => false]);
                        $updated++;
                    } catch (\Throwable $e) {
                        $errors[] = $cliente->nombres . ' ' . $cliente->apellidos . ': ' . $e->getMessage();
                    }
                }
            }

            $this->uploadResult = true;
            $parts = [];
            if ($created > 0) {
                $parts[] = "{$created} creado(s)";
            }
            if ($updated > 0) {
                $parts[] = "{$updated} actualizado(s)";
            }
            $this->uploadMessage = 'Sincronización con BioTime: ' . (implode(', ', $parts) ?: '0') . '.';
            if (count($errors) > 0) {
                $this->uploadMessage .= ' Errores: ' . implode('; ', array_slice($errors, 0, 5));
                if (count($errors) > 5) {
                    $this->uploadMessage .= '... (+' . (count($errors) - 5) . ' más)';
                }
            }
            if ($created > 0 || $updated > 0) {
                $this->loadEmployees();
            }
        } catch (\Throwable $e) {
            $this->uploadResult = false;
            $this->uploadMessage = $e->getMessage();
            try {
                IntegrationErrorLog::create([
                    'source' => 'biotime',
                    'payload' => ['action' => 'sync_clientes', 'department' => $this->uploadDepartmentId, 'area' => $this->uploadAreaId],
                    'error_message' => $e->getMessage(),
                ]);
            } catch (\Throwable $_) {
            }
        }
    }

    /**
     * Sincroniza un solo cliente con BioTime.
     * - biotime_state false: crear en BioTime y poner biotime_state true.
     * - biotime_state true y biotime_update true: actualizar en BioTime y poner biotime_update false.
     * - biotime_state true y biotime_update false: no llamar a la API; mensaje "ya sincronizado y sin cambios pendientes".
     */
    public function syncClienteToBiotime(int $clienteId)
    {
        $this->authorize('biotime.create');
        $this->validate([
            'uploadDepartmentId' => ['required', 'integer', 'min:1'],
            'uploadAreaId' => ['required', 'integer', 'min:1'],
        ], [], [
            'uploadDepartmentId' => 'departamento',
            'uploadAreaId' => 'área',
        ]);

        $this->uploadResult = null;
        $this->uploadMessage = '';

        $cliente = Cliente::where('estado_cliente', 'activo')->find($clienteId);
        if (! $cliente) {
            $this->uploadResult = false;
            $this->uploadMessage = 'Cliente no encontrado o no está activo.';
            return;
        }

        $companyId = $this->resolveCompanyIdForDepartment((int) $this->uploadDepartmentId);
        if ($companyId === null || $companyId <= 0) {
            $this->uploadResult = false;
            $this->uploadMessage = 'No se pudo obtener la empresa del departamento. Selecciona departamento y área.';
            return;
        }

        $clienteIdInt = (int) $cliente->id;
        $empCode = (string) $cliente->id;
        $synced = (bool) $cliente->biotime_state;
        $pendingUpdate = (bool) $cliente->biotime_update;

        if (! $synced) {
            try {
                $this->client->createEmployee([
                    'emp_code' => $empCode,
                    'department' => (int) $this->uploadDepartmentId,
                    'company' => $companyId,
                    'area' => [(int) $this->uploadAreaId],
                    'first_name' => $cliente->nombres ?? '',
                    'last_name' => $cliente->apellidos ?? '',
                ]);
                $cliente->update(['biotime_state' => true]);
                $this->uploadResult = true;
                $this->uploadMessage = $cliente->nombres . ' ' . $cliente->apellidos . ': creado en BioTime.';
            } catch (\Throwable $e) {
                $this->uploadResult = false;
                $this->uploadMessage = $cliente->nombres . ' ' . $cliente->apellidos . ': ' . $e->getMessage();
            }
            return;
        }

        if ($synced && ! $pendingUpdate) {
            $this->uploadResult = true;
            $this->uploadMessage = $cliente->nombres . ' ' . $cliente->apellidos . ': ya sincronizado y sin cambios pendientes.';
            return;
        }

        try {
            $emp = $this->client->getEmployee($clienteIdInt);
            $dept = $emp['department'] ?? [];
            $departmentId = is_array($dept) ? (int) ($dept['id'] ?? 0) : (int) $dept;
            $companyIdEmp = null;
            if (is_array($dept) && ! empty($dept['company']['id'])) {
                $companyIdEmp = (int) $dept['company']['id'];
            }
            if ($companyIdEmp === null || $companyIdEmp <= 0) {
                $companyIdEmp = $this->resolveCompanyIdForDepartment($departmentId) ?: $companyId;
            }
            $areaList = $emp['area'] ?? [];
            $areaIds = is_array($areaList) ? array_values(array_filter(array_map('intval', array_column($areaList, 'id')))) : [];
            if (empty($areaIds)) {
                $areaIds = [1];
            }

            $this->client->updateEmployee($clienteIdInt, [
                'emp_code' => $empCode,
                'department' => $departmentId,
                'company' => $companyIdEmp ?: $companyId,
                'area' => $areaIds,
                'first_name' => $cliente->nombres ?? '',
                'last_name' => $cliente->apellidos ?? '',
                'hire_date' => now()->format('Y-m-d'),
                'app_status' => 1,
            ]);
            $cliente->update(['biotime_update' => false]);
            $this->uploadResult = true;
            $this->uploadMessage = $cliente->nombres . ' ' . $cliente->apellidos . ': actualizado en BioTime.';
        } catch (\Throwable $e) {
            $this->uploadResult = false;
            $this->uploadMessage = $cliente->nombres . ' ' . $cliente->apellidos . ': ' . $e->getMessage();
        }
    }

    /**
     * Crea un cliente en el sistema a partir de un empleado de BioTime que no tiene cliente local.
     * El id del cliente será emp_code para mantener el vínculo con BioTime.
     */
    public function createClienteFromBiotimeEmployee(int $empCode, string $firstName = '', string $lastName = '')
    {
        $this->authorize('biotime.create');
        $this->syncMessage = '';

        if ($empCode <= 0) {
            $this->syncMessage = 'Código de empleado no válido.';
            return;
        }

        if (Cliente::find($empCode)) {
            $this->syncMessage = 'Ya existe un cliente con ese código (id ' . $empCode . ').';
            return;
        }

        $firstName = trim($firstName);
        $lastName = trim($lastName);

        if ($firstName === '' && $lastName === '') {
            try {
                $emp = $this->client->getEmployee($empCode);
                $firstName = trim($emp['first_name'] ?? '');
                $lastName = trim($emp['last_name'] ?? '');
            } catch (\Throwable $e) {
                $this->syncMessage = 'No se pudo obtener el empleado de BioTime: ' . $e->getMessage();
                return;
            }
        }

        if ($firstName === '' && $lastName === '') {
            $this->syncMessage = 'El empleado no tiene nombre en BioTime. No se puede crear el cliente.';
            return;
        }

        $userId = Auth::id();
        if (! $userId) {
            $this->syncMessage = 'Debes iniciar sesión para crear el cliente.';
            return;
        }

        try {
            $cliente = new Cliente;
            $cliente->id = $empCode;
            $cliente->tipo_documento = 'DNI';
            $cliente->numero_documento = (string) $empCode;
            $cliente->nombres = $firstName ?: 'Sin nombre';
            $cliente->apellidos = $lastName ?: '';
            $cliente->estado_cliente = 'inactivo';
            $cliente->biotime_state = true;
            $cliente->biotime_update = false;
            $cliente->created_by = $userId;
            $cliente->updated_by = $userId;
            $cliente->save();

            $this->syncMessage = 'Cliente creado: ' . $cliente->nombres . ' ' . $cliente->apellidos . ' (id ' . $empCode . ').';
            $this->loadEmployees();
        } catch (\Throwable $e) {
            $this->syncMessage = 'Error al crear el cliente: ' . $e->getMessage();
        }
    }

    /**
     * Elimina manualmente un empleado de BioTime.
     * La API de BioTime usa el id interno del empleado en la URL, no el emp_code.
     *
     * @param int $biotimeEmployeeId Id interno del empleado en BioTime (viene de $emp['id'] en la lista).
     * @param int $empCode Código del empleado (emp_code); si hay cliente local con ese id, se actualiza biotime_state.
     */
    public function deleteEmployeeFromBiotime(int $biotimeEmployeeId, int $empCode = 0)
    {
        $this->authorize('biotime.delete');
        $this->syncMessage = '';

        if ($biotimeEmployeeId <= 0) {
            $this->syncMessage = 'Id de empleado en BioTime no válido.';
            return;
        }

        try {
            $this->client->deleteEmployee($biotimeEmployeeId);

            $cliente = $empCode > 0 ? Cliente::find($empCode) : null;
            if ($cliente) {
                $cliente->update([
                    'biotime_state' => false,
                    'biotime_update' => false,
                ]);
            }

            $this->syncMessage = 'Empleado eliminado de BioTime.' . ($cliente ? ' Cliente local actualizado (biotime_state = false).' : '');
            $this->loadEmployees();
        } catch (\Throwable $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, '404') || str_contains($msg, 'No encontrado') || str_contains($msg, 'not found')) {
                $this->syncMessage = 'El empleado no existe en BioTime o ya fue eliminado.';
                $this->loadEmployees();
                return;
            }
            $this->syncMessage = 'Error al eliminar de BioTime: ' . $msg;
        }
    }

    public function render()
    {
        return view('livewire.biotime.biotime-sync-live');
    }
}
