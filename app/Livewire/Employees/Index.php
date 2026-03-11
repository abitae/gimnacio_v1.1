<?php

namespace App\Livewire\Employees;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Employee;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast, WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('employees.view');
    }

    public function render()
    {
        $query = Employee::query()
            ->when($this->search, fn ($q) => $q->where(function ($q2) {
                $q2->where('nombres', 'like', '%' . $this->search . '%')
                    ->orWhere('apellidos', 'like', '%' . $this->search . '%')
                    ->orWhere('documento', 'like', '%' . $this->search . '%');
            }))
            ->when($this->estadoFilter, fn ($q) => $q->where('estado', $this->estadoFilter))
            ->orderBy('apellidos');

        $employees = $query->paginate($this->perPage);

        return view('livewire.employees.index', ['employees' => $employees])
            ->layout('layouts.app', ['title' => 'Personal']);
    }
}
