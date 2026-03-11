<?php

namespace App\Livewire\Employees\Attendances;

use App\Models\Core\Employee;
use App\Models\Core\EmployeeAttendance;
use Carbon\Carbon;
use Livewire\Component;

class Report extends Component
{
    public string $mes = '';

    public string $anio = '';

    public ?int $employeeId = null;

    public function mount(): void
    {
        $this->authorize('attendance.view');
        $this->mes = request()->query('mes', (string) now()->month);
        $this->anio = request()->query('anio', (string) now()->year);
    }

    public function render()
    {
        $start = Carbon::createFromDate((int) $this->anio, (int) $this->mes, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $query = EmployeeAttendance::query()
            ->with('employee')
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->orderBy('fecha');

        $attendances = $query->get();
        $employees = Employee::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);
        $resumen = $attendances->groupBy('employee_id')->map(function ($items, $empId) {
            $tardanzas = $items->sum('tardanza_minutos');
            $dias = $items->count();
            return ['dias' => $dias, 'tardanza_minutos' => $tardanzas];
        });

        return view('livewire.employees.attendances.report', [
            'attendances' => $attendances,
            'employees' => $employees,
            'resumen' => $resumen,
            'start' => $start,
            'end' => $end,
        ])->layout('layouts.app', ['title' => 'Reporte de asistencia']);
    }
}
