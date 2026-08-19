<?php

namespace App\Livewire\Settings\AppPublicidad;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\AppPublicidad;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public int $perPage = 15;

    public array $modalState = ['create' => false, 'delete' => false];

    public ?int $publicidadId = null;

    public $imagen = null;

    public ?string $currentImagen = null;

    public array $formData = [
        'titulo' => '',
        'enlace_url' => '',
        'orden' => 0,
        'estado' => 'activo',
    ];

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('publicidad_app.ver');
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstadoFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('publicidad_app.crear');
        $this->resetForm();
        $this->modalState['create'] = true;
    }

    public function openEditModal(int $id): void
    {
        $this->authorize('publicidad_app.editar');
        $item = AppPublicidad::query()->find($id);
        if (! $item) {
            $this->flashToast('error', 'Publicidad no encontrada.');

            return;
        }

        $this->publicidadId = $item->id;
        $this->currentImagen = $item->imagen;
        $this->imagen = null;
        $this->formData = [
            'titulo' => $item->titulo,
            'enlace_url' => $item->enlace_url ?? '',
            'orden' => $item->orden,
            'estado' => $item->estado,
        ];
        $this->modalState['create'] = true;
    }

    public function openDeleteModal(int $id): void
    {
        $this->authorize('publicidad_app.eliminar');
        $this->publicidadId = $id;
        $this->modalState['delete'] = true;
    }

    public function save(): void
    {
        $this->authorize($this->publicidadId ? 'publicidad_app.editar' : 'publicidad_app.crear');

        $rules = [
            'formData.titulo' => 'required|string|max:80',
            'formData.enlace_url' => 'nullable|url|max:255',
            'formData.orden' => 'required|integer|min:0|max:999',
            'formData.estado' => 'required|in:activo,inactivo',
            'imagen' => $this->publicidadId
                ? 'nullable|image|max:4096'
                : 'required|image|max:4096',
        ];

        $this->validate($rules, [
            'formData.titulo.required' => 'El título es obligatorio.',
            'imagen.required' => 'Sube una imagen para mostrar en la app.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'formData.enlace_url.url' => 'El enlace no es una URL válida.',
        ]);

        try {
            $payload = [
                'titulo' => trim($this->formData['titulo']),
                'enlace_url' => trim((string) $this->formData['enlace_url']) ?: null,
                'orden' => (int) $this->formData['orden'],
                'estado' => $this->formData['estado'],
            ];

            if ($this->imagen) {
                if ($this->currentImagen && Storage::disk('public')->exists($this->currentImagen)) {
                    Storage::disk('public')->delete($this->currentImagen);
                }
                $payload['imagen'] = $this->imagen->store('app-publicidad', 'public');
            }

            if ($this->publicidadId) {
                AppPublicidad::query()->findOrFail($this->publicidadId)->update($payload);
                $this->flashToast('success', 'Publicidad actualizada.');
            } else {
                AppPublicidad::query()->create($payload);
                $this->flashToast('success', 'Publicidad creada. Se mostrará en el inicio de la app.');
            }

            $this->closeModal();
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function delete(): void
    {
        $this->authorize('publicidad_app.eliminar');
        try {
            $item = AppPublicidad::query()->findOrFail($this->publicidadId);
            if ($item->imagen && Storage::disk('public')->exists($item->imagen)) {
                Storage::disk('public')->delete($item->imagen);
            }
            $item->delete();
            $this->flashToast('success', 'Publicidad eliminada.');
            $this->closeModal();
            $this->resetPage();
        } catch (\Throwable $e) {
            report($e);
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function toggleEstado(int $id): void
    {
        $this->authorize('publicidad_app.editar');
        $item = AppPublicidad::query()->find($id);
        if (! $item) {
            $this->flashToast('error', 'Publicidad no encontrada.');

            return;
        }

        $item->estado = $item->estado === 'activo' ? 'inactivo' : 'activo';
        $item->save();
        $this->flashToast('success', 'Estado actualizado.');
    }

    public function closeModal(): void
    {
        $this->modalState = ['create' => false, 'delete' => false];
        $this->publicidadId = null;
        $this->imagen = null;
        $this->currentImagen = null;
        $this->resetForm();
        $this->resetValidation();
    }

    protected function resetForm(): void
    {
        $maxOrden = (int) AppPublicidad::query()->max('orden');
        $this->formData = [
            'titulo' => '',
            'enlace_url' => '',
            'orden' => $maxOrden + 1,
            'estado' => 'activo',
        ];
        $this->imagen = null;
        $this->currentImagen = null;
    }

    public function render()
    {
        $items = AppPublicidad::query()
            ->when($this->search, fn ($q) => $q->where('titulo', 'like', '%'.$this->search.'%'))
            ->when($this->estadoFilter, fn ($q) => $q->where('estado', $this->estadoFilter))
            ->orderBy('orden')
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.settings.app-publicidad.index', [
            'items' => $items,
        ])->layout('layouts.app', ['title' => 'Publicidad app']);
    }
}
