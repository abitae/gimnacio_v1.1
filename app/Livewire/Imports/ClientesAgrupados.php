<?php

namespace App\Livewire\Imports;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Import;
use App\Models\System\Sucursal;
use App\Services\Imports\ImportManagerService;
use App\Support\Imports\ImportType;
use Livewire\Component;
use Livewire\WithFileUploads;

class ClientesAgrupados extends Component
{
    use FlashesToast;
    use WithFileUploads;

    public ?int $sucursalId = null;

    public $archivo = null;

    public bool $stopOnError = false;

    public ?Import $importActual = null;

    /** @var array<string, mixed>|null */
    public ?array $resultadoPreview = null;

    public string $stateFilter = 'all';

    public function mount(): void
    {
        $this->authorize('importacion.ver');

        $sucursales = Sucursal::query()->where('estado', 'activa')->orderBy('nombre')->get();
        if ($sucursales->count() === 1) {
            $this->sucursalId = (int) $sucursales->first()->id;
        }
    }

    public function validar(ImportManagerService $manager): void
    {
        $this->authorize('importacion.crear');
        $this->validate($this->rules(), [
            'sucursalId.required' => 'Selecciona la sucursal destino.',
            'archivo.required' => 'Selecciona el archivo Clientes_Agrupados.xlsx.',
        ]);

        try {
            $out = $manager->preview($this->archivo, ImportType::CLIENTES_AGRUPADOS, (int) $this->sucursalId, [
                'stop_on_error' => $this->stopOnError,
            ]);
            $this->importActual = $out['import'];
            $this->resultadoPreview = $out['result'];
            $this->stateFilter = 'all';
            $this->flashToast('success', 'Vista previa generada. Revisa el informe antes de confirmar.');
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function confirmarImportacion(ImportManagerService $manager): void
    {
        $this->authorize('importacion.crear');
        if (! $this->importActual) {
            $this->flashToast('error', 'Primero valida un archivo.');

            return;
        }

        $import = Import::query()->find($this->importActual->id);
        if (! $import || $import->estado !== 'preview') {
            $this->flashToast('error', 'Importacion no valida o ya procesada.');

            return;
        }

        try {
            $out = $manager->commit($import, [
                'stop_on_error' => $this->stopOnError,
            ]);
            $this->importActual = $out['import'];
            $this->resultadoPreview = $out['result'];
            $this->reset('archivo');
            $this->flashToast('success', 'Actualizacion de clientes finalizada.');
            $this->redirect(route('importaciones.show', $this->importActual), navigate: true);
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(): array
    {
        return [
            'sucursalId' => ['required', 'exists:sucursales,id'],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls', 'max:'.(config('importacion.max_upload_kb', 10240))],
        ];
    }

    public function render()
    {
        $sucursales = Sucursal::query()->where('estado', 'activa')->orderBy('nombre')->get();

        return view('livewire.imports.clientes-agrupados', [
            'sucursales' => $sucursales,
        ])->layout('layouts.app', ['title' => 'Actualizar clientes agrupados']);
    }
}
