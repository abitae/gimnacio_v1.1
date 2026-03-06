<?php

namespace App\Livewire\Reports;

use App\Models\Core\Cliente;
use App\Models\WorkoutSession;
use Carbon\Carbon;
use Livewire\Component;

class Compliance extends Component
{
    public string $tipo_documento = 'DNI';
    public string $numero_documento = '';
    public ?Cliente $cliente = null;

    public function mount(): void
    {
        $this->authorize('ejercicios-rutinas.view');
    }

    public function buscarCliente(): void
    {
        $this->validate([
            'tipo_documento' => ['required', 'in:DNI,CE'],
            'numero_documento' => ['required', 'string', 'max:20'],
        ]);
        $this->cliente = Cliente::where('tipo_documento', $this->tipo_documento)
            ->where('numero_documento', $this->numero_documento)
            ->first();
        if (! $this->cliente) {
            session()->flash('error', 'No se encontró un cliente con ese documento.');
        }
    }

    public function render()
    {
        $data = [];
        if ($this->cliente) {
            $routines = $this->cliente->clientRoutines()->where('estado', 'activa')->get();
            foreach ($routines as $routine) {
                $frecuencia = $routine->routineTemplate?->frecuencia_dias_semana ?? 0;
                $semanaActual = Carbon::now()->startOfWeek();
                $sessionsThisWeek = WorkoutSession::where('client_routine_id', $routine->id)
                    ->where('fecha_hora', '>=', $semanaActual)
                    ->where('fecha_hora', '<', $semanaActual->copy()->addWeek())
                    ->count();
                $lastSession = WorkoutSession::where('client_routine_id', $routine->id)->orderByDesc('fecha_hora')->first();
                $diasDesdeUltima = $lastSession ? Carbon::now()->startOfDay()->diffInDays($lastSession->fecha_hora->startOfDay(), false) * -1 : null;
                $data[] = [
                    'rutina_nombre' => $routine->routineTemplate?->nombre ?? 'Rutina',
                    'frecuencia_planificada' => $frecuencia,
                    'sesiones_esta_semana' => $sessionsThisWeek,
                    'cumplimiento' => $frecuencia > 0 ? min(100, (int) round($sessionsThisWeek / $frecuencia * 100)) : 0,
                    'dias_desde_ultima' => $diasDesdeUltima,
                ];
            }
        }
        return view('livewire.reports.compliance', ['data' => $data]);
    }
}
