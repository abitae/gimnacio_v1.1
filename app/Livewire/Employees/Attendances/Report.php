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
        $this->authorize('asistencia_empleado.ver');
        $this->mes = request()->query('mes', (string) now()->month);
        $this->anio = request()->query('anio', (string) now()->year);
    }

    public function render()
    {
        $start = Carbon::createFromDate((int) $this->anio, (int) $this->mes, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $aggQuery = EmployeeAttendance::query()
            ->whereBetween('fecha', [$start->toDateString(), $end->toDateString()])
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->selectRaw('employee_id, COUNT(*) as dias, COALESCE(SUM(tardanza_minutos), 0) as tardanza_minutos')
            ->groupBy('employee_id');

        $resumen = $aggQuery->get()->mapWithKeys(fn ($row) => [
            (int) $row->employee_id => [
                'dias' => (int) $row->dias,
                'tardanza_minutos' => (int) $row->tardanza_minutos,
            ],
        ]);

        $employees = Employee::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);

        return view('livewire.employees.attendances.report', [
            'employees' => $employees,
            'resumen' => $resumen,
            'start' => $start,
            'end' => $end,
        ])->layout('layouts.app', ['title' => 'Reporte de asistencia']);
    }
}
