<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Employee;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?Employee $employee = null;

    public array $form = [
        'user_id' => null,
        'nombres' => '',
        'apellidos' => '',
        'documento' => '',
        'cargo' => '',
        'area' => '',
        'telefono' => '',
        'fecha_ingreso' => '',
        'estado' => 'activo',
    ];

    public function mount(?Employee $employee = null): void
    {
        $this->authorize($employee ? 'employees.update' : 'employees.create');
        $this->employee = $employee;
        if ($employee) {
            $this->form = [
                'user_id' => $employee->user_id,
                'nombres' => $employee->nombres,
                'apellidos' => $employee->apellidos,
                'documento' => $employee->documento,
                'cargo' => $employee->cargo ?? '',
                'area' => $employee->area ?? '',
                'telefono' => $employee->telefono ?? '',
                'fecha_ingreso' => $employee->fecha_ingreso?->format('Y-m-d') ?? '',
                'estado' => $employee->estado,
            ];
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.nombres' => 'required|string|max:80',
            'form.apellidos' => 'required|string|max:80',
            'form.documento' => 'required|string|max:30',
            'form.estado' => 'required|in:activo,inactivo',
        ]);

        try {
            $data = array_merge($this->form, [
                'user_id' => $this->form['user_id'] ?: null,
                'cargo' => $this->form['cargo'] ?: null,
                'area' => $this->form['area'] ?: null,
                'telefono' => $this->form['telefono'] ?: null,
                'fecha_ingreso' => $this->form['fecha_ingreso'] ?: null,
            ]);
            if ($this->employee) {
                $this->employee->update($data);
                $this->flashToast('success', 'Empleado actualizado.');
            } else {
                Employee::create($data);
                $this->flashToast('success', 'Empleado creado.');
            }
            $this->redirectRoute('employees.index', navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.employees.form')
            ->layout('layouts.app', ['title' => $this->employee ? 'Editar empleado' : 'Nuevo empleado']);
    }
}
