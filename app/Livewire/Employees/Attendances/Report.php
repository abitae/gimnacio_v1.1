<?php

namespace App\Livewire\Employees\Attendances;

use App\Models\Core\Employee;
use App\Services\EmployeeAttendanceService;
use Carbon\Carbon;
use Livewire\Component;

class Report extends Component
{
    public string $mes = '';

    public string $anio = '';

    public ?int $employeeId = null;

    protected EmployeeAttendanceService $attendanceService;

    public function boot(EmployeeAttendanceService $attendanceService): void
    {
        $this->attendanceService = $attendanceService;
    }

    public function mount(): void
    {
        $this->authorize('asistencia_empleado.ver');
        $this->mes = request()->query('mes', (string) now()->month);
        $this->anio = request()->query('anio', (string) now()->year);
        $this->employeeId = request()->query('employee_id') ? (int) request()->query('employee_id') : null;
    }

    public function render()
    {
        $start = Carbon::createFromDate((int) $this->anio, (int) $this->mes, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $reporte = $this->attendanceService->reporteResumen(
            $start,
            $end,
            $this->employeeId ?: null,
        );

        $detalle = $this->attendanceService->filasDetallePeriodo(
            $start,
            $end,
            $this->employeeId ?: null,
        );

        $employees = Employee::activos()->orderBy('apellidos')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']);

        $exportParams = array_filter([
            'mes' => $this->mes,
            'anio' => $this->anio,
            'employee_id' => $this->employeeId ?: null,
        ]);

        return view('livewire.employees.attendances.report', [
            'employees' => $employees,
            'filas' => $reporte['filas'],
            'totales' => $reporte['totales'],
            'detalle' => $detalle,
            'start' => $start,
            'end' => $end,
            'exportUrl' => route('employees.attendances.report.export', $exportParams),
        ])->layout('layouts.app', ['title' => 'Reporte de asistencia']);
    }
}
