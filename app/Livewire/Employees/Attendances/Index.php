<?php

namespace App\Livewire\Employees\Attendances;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Employee;
use App\Models\Core\EmployeeAttendance;
use App\Services\EmployeeAttendanceService;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast;
    use WithPagination;

    public string $fecha = '';

    public string $employeeSearch = '';

    public ?int $employeeId = null;

    public int $perPage = 20;

    public bool $mostrarModalRegistro = false;

    public array $registroForm = [
        'employee_id' => null,
        'hora_ingreso' => '',
        'hora_salida' => '',
        'observaciones' => '',
    ];

    protected $paginationTheme = 'tailwind';

    protected EmployeeAttendanceService $attendanceService;

    public function boot(EmployeeAttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    public function mount(): void
    {
        $this->authorize('asistencia_empleado.ver');
        $this->fecha = request()->query('fecha', now()->format('Y-m-d'));
        $this->employeeId = request()->query('employee_id') ? (int) request()->query('employee_id') : null;
    }

    public function updatingFecha(): void
    {
        $this->resetPage();
    }

    public function updatingEmployeeSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEmployeeId(): void
    {
        $this->resetPage();
    }

    public function updatingPerPage(): void
    {
        $this->resetPage();
    }

    public function registrarIngreso(int $employeeId): void
    {
        $this->authorize('asistencia_empleado.crear');

        try {
            $this->attendanceService->registrarIngreso($employeeId, $this->fecha);
            $this->flashToast('success', 'Ingreso registrado correctamente.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function registrarSalida(int $employeeId): void
    {
        $this->authorize('asistencia_empleado.crear');

        try {
            $this->attendanceService->registrarSalida($employeeId, $this->fecha);
            $this->flashToast('success', 'Salida registrada correctamente.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function abrirModalRegistro(int $employeeId): void
    {
        $this->authorize('asistencia_empleado.crear');

        $attendance = $this->attendanceService->attendanceForEmployeeOnDate($employeeId, $this->fecha);
        if ($attendance !== null) {
            $this->flashToast('error', 'Ya existe un registro de asistencia para este empleado en la fecha seleccionada.');

            return;
        }

        $this->registroForm = [
            'employee_id' => $employeeId,
            'hora_ingreso' => now()->format('H:i'),
            'hora_salida' => '',
            'observaciones' => '',
        ];
        $this->mostrarModalRegistro = true;
    }

    public function cerrarModalRegistro(): void
    {
        $this->mostrarModalRegistro = false;
        $this->registroForm = [
            'employee_id' => null,
            'hora_ingreso' => '',
            'hora_salida' => '',
            'observaciones' => '',
        ];
    }

    public function guardarRegistroManual(): void
    {
        $this->authorize('asistencia_empleado.crear');

        $this->validate([
            'registroForm.employee_id' => 'required|exists:employees,id',
            'registroForm.hora_ingreso' => 'nullable|string',
            'registroForm.hora_salida' => 'nullable|string',
            'registroForm.observaciones' => 'nullable|string|max:500',
        ]);

        try {
            $this->attendanceService->registrarManual([
                'employee_id' => (int) $this->registroForm['employee_id'],
                'fecha' => $this->fecha,
                'hora_ingreso' => $this->registroForm['hora_ingreso'] ?: null,
                'hora_salida' => $this->registroForm['hora_salida'] ?: null,
                'observaciones' => $this->registroForm['observaciones'] ?: null,
            ]);
            $this->cerrarModalRegistro();
            $this->flashToast('success', 'Asistencia registrada correctamente.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $query = EmployeeAttendance::query()
            ->with(['employee', 'registradoPor'])
            ->when($this->fecha, fn ($q) => $q->whereDate('fecha', $this->fecha))
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->orderByDesc('fecha')
            ->orderByDesc('hora_ingreso');

        $attendances = $query->paginate($this->perPage);
        $employees = Employee::activos()->orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']);

        $empleadosBusqueda = $this->attendanceService->searchEmployees($this->employeeSearch);
        $asistenciasBusqueda = $this->attendanceService->attendancesForEmployeesOnDate(
            $empleadosBusqueda->pluck('id'),
            $this->fecha,
        );

        $empleadoModal = ! empty($this->registroForm['employee_id'])
            ? Employee::query()->find($this->registroForm['employee_id'])
            : null;

        return view('livewire.employees.attendances.index', [
            'attendances' => $attendances,
            'employees' => $employees,
            'empleadosBusqueda' => $empleadosBusqueda,
            'asistenciasBusqueda' => $asistenciasBusqueda,
            'empleadoModal' => $empleadoModal,
            'puedeRegistrar' => auth()->user()?->can('asistencia_empleado.crear') ?? false,
        ])->layout('layouts.app', ['title' => 'Asistencia de personal']);
    }
}
