<?php

namespace App\Livewire\Nutrition\Goals;

use App\Models\Core\NutritionGoal;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public NutritionGoal $goal;

    public int $perPage = 10;

    protected $paginationTheme = 'tailwind';

    public function mount(NutritionGoal $goal): void
    {
        $this->authorize('gestion-nutricional.view');
        $this->goal = $goal;
    }

    public function render()
    {
        $this->goal->load(['cliente', 'trainerUser']);
        $progress = $this->goal->progress()->with('registradoPor')->orderByDesc('fecha')->paginate($this->perPage);

        return view('livewire.nutrition.goals.show', [
            'progress' => $progress,
        ])->layout('layouts.app', ['title' => 'Objetivo: ' . ($this->goal->objetivo_personalizado ?: (\App\Models\Core\NutritionGoal::OBJETIVOS[$this->goal->objetivo] ?? $this->goal->objetivo))]);
    }
}
