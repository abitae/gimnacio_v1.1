<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Tag;
use Livewire\Component;

class CrmTagsLive extends Component
{
    use FlashesToast;

    public $search = '';

    public $modalForm = false;

    public $editingTagId = null;

    public $nombre = '';

    public $color = '#6366f1';

    public bool $confirmDeleteTag = false;

    public ?int $tagIdPendingDelete = null;

    public function mount()
    {
        $this->authorize('crm.ver');
    }

    public function openCreate()
    {
        $this->authorize('crm.crear');
        $this->editingTagId = null;
        $this->nombre = '';
        $this->color = '#6366f1';
        $this->modalForm = true;
    }

    public function openEdit(int $id)
    {
        $this->authorize('crm.editar');
        $tag = Tag::find($id);
        if (! $tag) {
            return;
        }
        $this->editingTagId = $id;
        $this->nombre = $tag->nombre;
        $this->color = $tag->color ?? '#6366f1';
        $this->modalForm = true;
    }

    public function save()
    {
        $this->authorize($this->editingTagId ? 'crm.editar' : 'crm.crear');
        $this->validate([
            'nombre' => 'required|string|max:60',
            'color' => 'nullable|string|max:20',
        ]);
        if ($this->editingTagId) {
            $tag = Tag::findOrFail($this->editingTagId);
            $tag->update(['nombre' => $this->nombre, 'color' => $this->color ?: null]);
        } else {
            Tag::create(['nombre' => $this->nombre, 'color' => $this->color ?: null]);
        }
        $this->modalForm = false;
        $this->editingTagId = null;
        $this->flashToast('success', 'Etiqueta guardada');
    }

    public function requestDeleteTag(int $id): void
    {
        $this->authorize('crm.eliminar');
        $this->tagIdPendingDelete = $id;
        $this->confirmDeleteTag = true;
    }

    public function cancelDeleteTag(): void
    {
        $this->confirmDeleteTag = false;
        $this->tagIdPendingDelete = null;
    }

    public function deleteTag(?int $id = null)
    {
        $this->authorize('crm.eliminar');
        $id ??= $this->tagIdPendingDelete;
        $tag = $id ? Tag::find($id) : null;
        if ($tag) {
            $tag->delete();
            $this->flashToast('success', 'Etiqueta eliminada');
        }
        $this->cancelDeleteTag();
    }

    public function closeModal()
    {
        $this->modalForm = false;
        $this->editingTagId = null;
    }

    public function getTagsProperty()
    {
        $q = Tag::query()->orderBy('nombre');
        if ($this->search) {
            $q->where('nombre', 'like', '%'.$this->search.'%');
        }

        return $q->get();
    }

    public function render()
    {
        return view('livewire.crm.crm-tags-live', [
            'tags' => $this->getTagsProperty(),
        ]);
    }
}
