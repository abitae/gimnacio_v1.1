<?php

namespace App\Livewire\Imports;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Import;
use App\Models\System\Sucursal;
use App\Services\Imports\ExcelColumnAnalyzerService;
use App\Services\Imports\ImportManagerService;
use App\Support\Imports\ImportType;
use App\Support\Imports\InitialLoadCatalog;
use Livewire\Component;
use Livewire\WithFileUploads;

class Dashboard extends Component
{
    use FlashesToast;
    use WithFileUploads;

    public ?int $sucursalId = null;

    public string $tipo = ImportType::USUARIOS;

    public $archivo = null;

    public string $duplicateMode = 'crear_o_actualizar';

    public bool $stopOnError = false;

    public ?Import $importActual = null;

    /** @var array<string, mixed>|null */
    public ?array $resultadoPreview = null;

    /** @var array<string, mixed>|null */
    public ?array $columnAnalysis = null;

    public string $phaseFilter = 'all';

    public string $stateFilter = 'all';

    public function mount(): void
    {
        $this->authorize('importacion.ver');
    }

    public function analizarColumnas(ExcelColumnAnalyzerService $analyzer): void
    {
        $this->authorize('importacion.ver');

        $config = InitialLoadCatalog::for($this->tipo);

        $this->validate([
            'archivo' => ['required', 'file', 'mimes:'.$config['accepted_mimes'], 'max:'.(config('importacion.max_upload_kb', 10240))],
        ], [
            'archivo.required' => 'Selecciona un archivo Excel para analizar.',
        ]);

        try {
            $this->columnAnalysis = $analyzer->analyze($this->archivo->getRealPath(), $this->tipo);
            $this->flashToast('success', 'Analisis de columnas generado. Revisa encabezados, faltantes y muestra de filas.');
        } catch (\Throwable $e) {
            $this->columnAnalysis = null;
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function validar(ImportManagerService $manager): void
    {
        $this->authorize('importacion.crear');

        $config = InitialLoadCatalog::for($this->tipo);
        $rules = [
            'sucursalId' => ['required', 'exists:sucursales,id'],
            'tipo' => ['required', 'in:'.implode(',', ImportType::implemented())],
            'archivo' => ['required', 'file', 'mimes:'.$config['accepted_mimes'], 'max:'.(config('importacion.max_upload_kb', 10240))],
        ];

        if ($this->tipo === ImportType::CLIENTES) {
            $rules['duplicateMode'] = ['required', 'in:omitir,actualizar,crear_o_actualizar'];
        }

        $this->validate($rules, [
            'sucursalId.required' => 'Selecciona la sucursal destino.',
            'archivo.required' => 'Selecciona un archivo Excel.',
        ]);

        try {
            if ($this->columnAnalysis === null) {
                $this->columnAnalysis = app(ExcelColumnAnalyzerService::class)->analyze($this->archivo->getRealPath(), $this->tipo);
            }

            $out = $manager->preview($this->archivo, $this->tipo, (int) $this->sucursalId, [
                'duplicate_mode' => $this->duplicateMode,
                'stop_on_error' => $this->stopOnError,
            ]);

            $this->importActual = $out['import'];
            $this->resultadoPreview = $out['result'];
            $this->phaseFilter = 'all';
            $this->stateFilter = 'all';

            $this->flashToast('success', 'Vista previa generada. Revisa el analisis y las filas antes de confirmar la importacion.');
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
                'duplicate_mode' => $this->duplicateMode,
                'stop_on_error' => $this->stopOnError,
            ]);

            $this->importActual = $out['import'];
            $this->resultadoPreview = $out['result'];
            $this->reset('archivo');
            $this->flashToast('success', 'Importacion finalizada.');
            $this->redirect(route('importaciones.show', $this->importActual), navigate: true);
        } catch (\Throwable $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function updatedTipo(): void
    {
        $this->columnAnalysis = null;
        $this->resultadoPreview = null;
        $this->importActual = null;
        $this->phaseFilter = 'all';
        $this->stateFilter = 'all';
    }

    public function updatedArchivo(): void
    {
        $this->columnAnalysis = null;
        $this->resultadoPreview = null;
        $this->importActual = null;
        $this->phaseFilter = 'all';
        $this->stateFilter = 'all';
    }

    public function render()
    {
        $sucursales = Sucursal::query()->where('estado', 'activa')->orderBy('nombre')->get();
        $catalog = InitialLoadCatalog::all();

        return view('livewire.imports.dashboard', [
            'sucursales' => $sucursales,
            'tipos' => ImportType::labels(),
            'tiposImplementados' => ImportType::implemented(),
            'catalog' => $catalog,
            'selectedConfig' => $catalog[$this->tipo] ?? null,
        ])->layout('layouts.app', ['title' => 'Carga inicial de datos']);
    }
}
