<?php

namespace App\Livewire\Employees\Attendances;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Employee;
use App\Models\Core\EmployeeAttendance;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public array $form = [
        'employee_id' => null,
        'fecha' => '',
        'hora_ingreso' => '',
        'hora_salida' => '',
        'observaciones' => '',
    ];

    public function mount(): void
    {
        $this->authorize('attendance.create');
        $this->form['fecha'] = request()->query('fecha', now()->format('Y-m-d'));
        $this->form['employee_id'] = request()->query('employee_id');
    }

    public function save(): void
    {
        $this->validate([
            'form.employee_id' => 'required|exists:employees,id',
            'form.fecha' => 'required|date',
            'form.hora_ingreso' => 'nullable|string',
            'form.hora_salida' => 'nullable|string',
        ]);

        $exists = EmployeeAttendance::where('employee_id', $this->form['employee_id'])
            ->whereDate('fecha', $this->form['fecha'])
            ->first();
        if ($exists) {
            $this->flashToast('error', 'Ya existe un registro de asistencia para este empleado en esta fecha.');
            return;
        }

        try {
            EmployeeAttendance::create([
                'employee_id' => $this->form['employee_id'],
                'fecha' => $this->form['fecha'],
                'hora_ingreso' => $this->form['hora_ingreso'] ?: null,
                'hora_salida' => $this->form['hora_salida'] ?: null,
                'observaciones' => $this->form['observaciones'] ?: null,
                'registrado_por' => auth()->id(),
            ]);
            $this->flashToast('success', 'Asistencia registrada.');
            $this->redirectRoute('employees.attendances.index', navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $employees = Employee::activos()->orderBy('apellidos')->get(['id', 'nombres', 'apellidos']);

        return view('livewire.employees.attendances.form', ['employees' => $employees])
            ->layout('layouts.app', ['title' => 'Registrar asistencia']);
    }
}
