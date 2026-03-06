<?php

namespace App\Livewire\Routines\Templates;

use App\Models\RoutineTemplate;
use Livewire\Component;

class Show extends Component
{
    public RoutineTemplate $template;

    public function mount(RoutineTemplate $template): void
    {
        $this->authorize('ejercicios-rutinas.view');
        $this->template = $template->load(['days.exercises.exercise']);
    }

    public function render()
    {
        return view('livewire.routines.templates.show');
    }
}
