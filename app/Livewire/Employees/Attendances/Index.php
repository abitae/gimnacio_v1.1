<?php

namespace App\Livewire\Employees\Attendances;

use App\Models\Core\EmployeeAttendance;
use Carbon\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $fecha = '';

    public ?int $employeeId = null;

    public int $perPage = 20;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('attendance.view');
        $this->fecha = request()->query('fecha', now()->format('Y-m-d'));
    }

    public function render()
    {
        $query = EmployeeAttendance::query()
            ->with(['employee', 'registradoPor'])
            ->when($this->fecha, fn ($q) => $q->whereDate('fecha', $this->fecha))
            ->when($this->employeeId, fn ($q) => $q->where('employee_id', $this->employeeId))
            ->orderByDesc('fecha')->orderByDesc('hora_ingreso');

        $attendances = $query->paginate($this->perPage);
        $employees = \App\Models\Core\Employee::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);

        return view('livewire.employees.attendances.index', [
            'attendances' => $attendances,
            'employees' => $employees,
        ])->layout('layouts.app', ['title' => 'Asistencia de personal']);
    }
}
