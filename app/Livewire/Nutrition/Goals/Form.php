<?php

namespace App\Livewire\Nutrition\Goals;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\NutritionGoal;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?NutritionGoal $goal = null;

    public ?int $cliente_id = null;

    public array $form = [
        'cliente_id' => null,
        'trainer_user_id' => null,
        'objetivo' => 'mantener_peso',
        'objetivo_personalizado' => '',
        'fecha_inicio' => '',
        'fecha_objetivo' => '',
        'observaciones' => '',
        'estado' => 'activo',
    ];

    public function mount(): void
    {
        $this->authorize($this->goal ? 'gestion-nutricional.update' : 'gestion-nutricional.create');
        $this->cliente_id = (int) request()->query('cliente_id');
        if ($this->goal) {
            $this->form = [
                'cliente_id' => $this->goal->cliente_id,
                'trainer_user_id' => $this->goal->trainer_user_id,
                'objetivo' => $this->goal->objetivo,
                'objetivo_personalizado' => $this->goal->objetivo_personalizado ?? '',
                'fecha_inicio' => $this->goal->fecha_inicio->format('Y-m-d'),
                'fecha_objetivo' => $this->goal->fecha_objetivo?->format('Y-m-d') ?? '',
                'observaciones' => $this->goal->observaciones ?? '',
                'estado' => $this->goal->estado,
            ];
        } else {
            $this->form['cliente_id'] = $this->cliente_id ?: null;
            $this->form['trainer_user_id'] = auth()->id();
            $this->form['fecha_inicio'] = now()->format('Y-m-d');
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.cliente_id' => 'required|exists:clientes,id',
            'form.trainer_user_id' => 'required|exists:users,id',
            'form.objetivo' => 'required|string|in:' . implode(',', array_keys(NutritionGoal::OBJETIVOS)),
            'form.fecha_inicio' => 'required|date',
            'form.fecha_objetivo' => 'nullable|date|after_or_equal:form.fecha_inicio',
            'form.estado' => 'required|in:activo,cumplido,cancelado',
        ]);

        try {
            if ($this->goal) {
                $this->goal->update($this->form);
                $this->flashToast('success', 'Objetivo actualizado.');
            } else {
                NutritionGoal::create($this->form);
                $this->flashToast('success', 'Objetivo creado.');
            }
            $this->redirectRoute('gestion-nutricional.objetivos.index', navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $clientes = \App\Models\Core\Cliente::where('estado_cliente', 'activo')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']);
        $trainers = \App\Models\User::role(['trainer', 'nutricionista', 'administrador', 'super_administrador'])->orderBy('name')->get(['id', 'name']);

        return view('livewire.nutrition.goals.form', [
            'clientes' => $clientes,
            'trainers' => $trainers,
        ])->layout('layouts.app', ['title' => $this->goal ? 'Editar objetivo' : 'Nuevo objetivo']);
    }
}
