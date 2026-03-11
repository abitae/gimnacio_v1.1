<?php

namespace App\Livewire\Nutrition;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\Cliente;
use App\Models\Core\HealthRecord;
use Livewire\Component;

class HealthRecordForm extends Component
{
    use FlashesToast;

    /** ID del cliente cuando el formulario se usa dentro de un modal. */
    public ?int $clienteId = null;

    public ?Cliente $cliente = null;

    public ?HealthRecord $healthRecord = null;

    public array $form = [
        'enfermedades' => '',
        'alergias' => '',
        'medicacion' => '',
        'restricciones_medicas' => '',
        'lesiones' => '',
        'observaciones' => '',
    ];

    public function mount(?Cliente $cliente = null): void
    {
        $this->authorize('gestion-nutricional.update');
        if ($cliente) {
            $this->clienteId = $cliente->id;
            $this->cliente = $cliente;
        } elseif ($this->clienteId) {
            $this->cliente = Cliente::findOrFail($this->clienteId);
        }
        if (! $this->cliente) {
            return;
        }
        $this->healthRecord = HealthRecord::where('cliente_id', $this->cliente->id)->first();
        if ($this->healthRecord) {
            $this->form = [
                'enfermedades' => $this->healthRecord->enfermedades ?? '',
                'alergias' => $this->healthRecord->alergias ?? '',
                'medicacion' => $this->healthRecord->medicacion ?? '',
                'restricciones_medicas' => $this->healthRecord->restricciones_medicas ?? '',
                'lesiones' => $this->healthRecord->lesiones ?? '',
                'observaciones' => $this->healthRecord->observaciones ?? '',
            ];
        }
    }

    public function updatedClienteId($value): void
    {
        if ($value) {
            $this->cliente = Cliente::find($value);
            $this->loadHealthRecord();
        } else {
            $this->cliente = null;
            $this->healthRecord = null;
            $this->resetForm();
        }
    }

    protected function loadHealthRecord(): void
    {
        if (! $this->cliente) {
            return;
        }
        $this->healthRecord = HealthRecord::where('cliente_id', $this->cliente->id)->first();
        if ($this->healthRecord) {
            $this->form = [
                'enfermedades' => $this->healthRecord->enfermedades ?? '',
                'alergias' => $this->healthRecord->alergias ?? '',
                'medicacion' => $this->healthRecord->medicacion ?? '',
                'restricciones_medicas' => $this->healthRecord->restricciones_medicas ?? '',
                'lesiones' => $this->healthRecord->lesiones ?? '',
                'observaciones' => $this->healthRecord->observaciones ?? '',
            ];
        } else {
            $this->resetForm();
        }
    }

    protected function resetForm(): void
    {
        $this->form = [
            'enfermedades' => '',
            'alergias' => '',
            'medicacion' => '',
            'restricciones_medicas' => '',
            'lesiones' => '',
            'observaciones' => '',
        ];
    }

    public function save(): void
    {
        $this->validate([
            'form.enfermedades' => 'nullable|string|max:2000',
            'form.alergias' => 'nullable|string|max:2000',
            'form.medicacion' => 'nullable|string|max:2000',
            'form.restricciones_medicas' => 'nullable|string|max:2000',
            'form.lesiones' => 'nullable|string|max:2000',
            'form.observaciones' => 'nullable|string|max:2000',
        ]);

        if (! $this->cliente) {
            $this->flashToast('error', 'Cliente no encontrado.');
            return;
        }

        try {
            $data = array_merge($this->form, [
                'cliente_id' => $this->cliente->id,
                'actualizado_por' => auth()->id(),
            ]);
            if ($this->healthRecord) {
                $this->healthRecord->update($data);
                $this->flashToast('success', 'Datos de salud actualizados.');
            } else {
                HealthRecord::create($data);
                $this->healthRecord = HealthRecord::where('cliente_id', $this->cliente->id)->first();
                $this->flashToast('success', 'Datos de salud guardados.');
            }
            $this->dispatch('close-salud-modal');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.nutrition.health-record-form');
    }
}
