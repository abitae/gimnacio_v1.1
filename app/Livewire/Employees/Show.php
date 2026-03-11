<?php

namespace App\Livewire\Employees;

use App\Models\Core\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Employee $employee;

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(Employee $employee): void
    {
        $this->authorize('employees.view');
        $this->employee = $employee;
    }

    public function render()
    {
        $attendances = $this->employee->attendances()->with('registradoPor')->orderByDesc('fecha')->paginate($this->perPage);

        return view('livewire.employees.show', ['attendances' => $attendances])
            ->layout('layouts.app', ['title' => $this->employee->nombre_completo]);
    }
}
