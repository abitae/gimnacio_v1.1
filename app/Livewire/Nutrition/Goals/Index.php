<?php

namespace App\Livewire\Nutrition\Goals;

use App\Models\Core\Cliente;
use App\Models\Core\NutritionGoal;
use App\Support\PermissionCatalog;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public ?int $clienteId = null;

    public ?int $trainerFilter = null;

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('gestion-nutricional.view');
        $this->clienteId = request()->has('cliente_id') ? (int) request()->query('cliente_id') : null;
    }

    public function render()
    {
        $query = NutritionGoal::query()
            ->with(['cliente', 'trainerUser'])
            ->when($this->clienteId, fn ($q) => $q->where('cliente_id', $this->clienteId))
            ->when($this->trainerFilter, fn ($q) => $q->where('trainer_user_id', $this->trainerFilter))
            ->orderByDesc('fecha_inicio');

        $goals = $query->paginate($this->perPage);
        $clientes = Cliente::where('estado_cliente', 'activo')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']);
        $trainers = \App\Models\User::role(['trainer', 'nutricionista', 'administrador', PermissionCatalog::SUPER_ADMIN_ROLE_NAME])->orderBy('name')->get(['id', 'name']);

        return view('livewire.nutrition.goals.index', [
            'goals' => $goals,
            'clientes' => $clientes,
            'trainers' => $trainers,
        ])->layout('layouts.app', ['title' => 'Objetivos nutricionales']);
    }
}
