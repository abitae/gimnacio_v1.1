<?php

namespace App\Livewire\Rentals\Bookings;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Rental;
use App\Models\Core\RentableSpace;
use Carbon\Carbon;
use Livewire\Component;

class Form extends Component
{
    use FlashesToast;

    public ?Rental $rental = null;

    public ?int $rentable_space_id = null;

    public ?string $fechaPreset = null;

    public array $form = [
        'rentable_space_id' => null,
        'cliente_id' => null,
        'nombre_externo' => '',
        'documento_externo' => '',
        'fecha' => '',
        'hora_inicio' => '',
        'hora_fin' => '',
        'precio' => '',
        'observaciones' => '',
        'estado' => 'reservado',
    ];

    public function mount(?Rental $rental = null): void
    {
        $this->authorize('rentals.create');
        $this->rental = $rental;
        if ($rental) {
            $this->form = [
                'rentable_space_id' => $rental->rentable_space_id,
                'cliente_id' => $rental->cliente_id,
                'nombre_externo' => $rental->nombre_externo ?? '',
                'documento_externo' => $rental->documento_externo ?? '',
                'fecha' => $rental->fecha->format('Y-m-d'),
                'hora_inicio' => $rental->hora_inicio?->format('H:i') ?? '',
                'hora_fin' => $rental->hora_fin?->format('H:i') ?? '',
                'precio' => (string) $rental->precio,
                'observaciones' => $rental->observaciones ?? '',
                'estado' => $rental->estado,
            ];
        } else {
            $this->form['fecha'] = request()->query('fecha', now()->format('Y-m-d'));
            $this->form['rentable_space_id'] = request()->query('space_id') ?: null;
            $this->rentable_space_id = $this->form['rentable_space_id'];
        }
    }

    public function save(): void
    {
        $this->validate([
            'form.rentable_space_id' => 'required|exists:rentable_spaces,id',
            'form.fecha' => 'required|date',
            'form.hora_inicio' => 'required|string',
            'form.hora_fin' => 'required|string',
            'form.precio' => 'required|numeric|min:0',
            'form.estado' => 'required|in:reservado,confirmado,pagado,cancelado,finalizado',
        ]);

        $horaInicio = Carbon::parse($this->form['fecha'] . ' ' . $this->form['hora_inicio']);
        $horaFin = Carbon::parse($this->form['fecha'] . ' ' . $this->form['hora_fin']);
        if ($horaFin <= $horaInicio) {
            $this->addError('form.hora_fin', 'La hora fin debe ser posterior a la hora de inicio.');
            return;
        }

        $solapado = Rental::where('rentable_space_id', $this->form['rentable_space_id'])
            ->whereDate('fecha', $this->form['fecha'])
            ->whereNotIn('estado', ['cancelado'])
            ->when($this->rental, fn ($q) => $q->where('id', '!=', $this->rental->id))
            ->where(function ($q) use ($horaInicio, $horaFin) {
                $q->where(function ($q2) use ($horaInicio, $horaFin) {
                    $q2->where('hora_inicio', '<', $horaFin->format('H:i:s'))
                        ->where('hora_fin', '>', $horaInicio->format('H:i:s'));
                });
            })
            ->exists();
        if ($solapado) {
            $this->flashToast('error', 'El horario se solapa con otra reserva.');
            return;
        }

        try {
            $data = [
                'rentable_space_id' => $this->form['rentable_space_id'],
                'cliente_id' => $this->form['cliente_id'] ?: null,
                'nombre_externo' => $this->form['nombre_externo'] ?: null,
                'documento_externo' => $this->form['documento_externo'] ?: null,
                'fecha' => $this->form['fecha'],
                'hora_inicio' => $this->form['hora_inicio'],
                'hora_fin' => $this->form['hora_fin'],
                'precio' => $this->form['precio'],
                'estado' => $this->form['estado'],
                'observaciones' => $this->form['observaciones'] ?: null,
                'registrado_por' => auth()->id(),
            ];
            if ($this->rental) {
                $this->rental->update($data);
                $this->flashToast('success', 'Reserva actualizada.');
            } else {
                Rental::create($data);
                $this->flashToast('success', 'Reserva creada.');
            }
            $this->redirectRoute('rentals.calendar.index', ['fecha' => $this->form['fecha']], navigate: true);
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $spaces = RentableSpace::activos()->orderBy('nombre')->get();
        $clientes = \App\Models\Core\Cliente::where('estado_cliente', 'activo')->orderBy('nombres')->get(['id', 'nombres', 'apellidos']);

        return view('livewire.rentals.bookings.form', [
            'spaces' => $spaces,
            'clientes' => $clientes,
        ])->layout('layouts.app', ['title' => $this->rental ? 'Editar reserva' : 'Nueva reserva']);
    }
}
