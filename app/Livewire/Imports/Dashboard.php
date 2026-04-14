<?php

namespace App\Livewire\Imports;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Import;
use App\Models\System\Sucursal;
use App\Services\Imports\ImportManagerService;
use App\Support\Imports\ImportType;
use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use FlashesToast;
    use WithFileUploads;

    public ?int $sucursalId = null;

    public string $tipo = ImportType::CLIENTES;

    public $archivo = null;

    public string $duplicateMode = 'crear_o_actualizar';

    public bool $stopOnError = false;

    public ?Import $importActual = null;

    /** @var array<string, mixed>|null */
    public ?array $resultadoPreview = null;

    public function mount(): void
    {
        $this->authorize('importacion.ver');
    }

    public function validar(ImportManagerService $manager): void
    {
        $this->authorize('importacion.crear');
        $rules = [
            'sucursalId' => ['required', 'exists:sucursales,id'],
            'tipo' => ['required', 'in:'.implode(',', ImportType::implemented())],
            'archivo' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:'.(config('importacion.max_upload_kb', 10240))],
        ];
        if ($this->tipo === ImportType::CLIENTES) {
            $rules['duplicateMode'] = ['required', 'in:omitir,actualizar,crear_o_actualizar'];
        }
        $this->validate($rules, [
            'sucursalId.required' => 'Selecciona la sucursal destino.',
            'archivo.required' => 'Selecciona un archivo Excel.',
        ]);

        try {
            $out = $manager->preview($this->archivo, $this->tipo, (int) $this->sucursalId, [
                'duplicate_mode' => $this->duplicateMode,
                'stop_on_error' => $this->stopOnError,
            ]);
            $this->importActual = $out['import'];
            $this->resultadoPreview = $out['result'];
            $this->flashToast('success', 'Vista previa generada. Revisa filas y confirma la importación.');
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
            $this->flashToast('error', 'Importación no válida o ya procesada.');

            return;
        }

        try {
            $out = $manager->commit($import, [
                'duplicate_mode' => $this->duplicateMode,
                'stop_on_error' => $this->stopOnError,
            ]);
            $this->importActual = $out['import'];
            $this->resultadoPreview = $out['result'];
            $this->reset('archivo');
            $this->flashToast('success', 'Importación finalizada.');
            $this->redirect(route('importaciones.show', $this->importActual), navigate: true);
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function render()
    {
        $sucursales = Sucursal::query()->where('estado', 'activa')->orderBy('nombre')->get();

        return view('livewire.imports.dashboard', [
            'sucursales' => $sucursales,
            'tipos' => ImportType::labels(),
            'tiposImplementados' => ImportType::implemented(),
        ])->layout('layouts.app', ['title' => 'Importación de datos']);
    }
}
