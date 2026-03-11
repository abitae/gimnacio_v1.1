<?php

namespace App\Livewire\Nutrition\Progress;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\NutritionGoal;
use App\Models\Core\NutritionGoalProgress;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public NutritionGoal $goal;

    public array $form = [
        'fecha' => '',
        'peso' => '',
        'observaciones' => '',
        'adherencia' => '',
        'progreso_general' => '',
    ];

    public function mount(NutritionGoal $goal): void
    {
        $this->authorize('gestion-nutricional.create');
        $this->goal = $goal;
        $this->form['fecha'] = now()->format('Y-m-d');
    }

    public function save(): void
    {
        $this->validate([
            'form.fecha' => 'required|date',
            'form.peso' => 'nullable|numeric|min:0',
            'form.observaciones' => 'nullable|string|max:2000',
            'form.adherencia' => 'nullable|string|max:40',
            'form.progreso_general' => 'nullable|string|max:2000',
        ]);

        try {
            NutritionGoalProgress::create([
                'nutrition_goal_id' => $this->goal->id,
                'fecha' => $this->form['fecha'],
                'peso' => $this->form['peso'] !== '' ? $this->form['peso'] : null,
                'observaciones' => $this->form['observaciones'] ?: null,
                'adherencia' => $this->form['adherencia'] ?: null,
                'progreso_general' => $this->form['progreso_general'] ?: null,
                'registrado_por' => auth()->id(),
            ]);
            $this->flashToast('success', 'Seguimiento registrado.');
            $this->redirectRoute('gestion-nutricional.objetivos.show', ['goal' => $this->goal], navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.nutrition.progress.form')
            ->layout('layouts.app', ['title' => 'Registrar seguimiento']);
    }
}
