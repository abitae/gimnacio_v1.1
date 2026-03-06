<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Lead;
use App\Models\Core\Cliente;
use App\Models\Crm\Tag;
use Livewire\Component;

class TagPickerLive extends Component
{
    use FlashesToast;

    public string $entityType = 'lead'; // 'lead' | 'cliente'
    public ?int $leadId = null;
    public ?int $clienteId = null;
    public array $selectedTagIds = [];

    public function mount(string $entityType, ?int $leadId = null, ?int $clienteId = null)
    {
        $this->entityType = $entityType;
        $this->leadId = $leadId;
        $this->clienteId = $clienteId;
        $this->loadCurrentTags();
    }

    protected function loadCurrentTags(): void
    {
        if ($this->entityType === 'lead' && $this->leadId) {
            $lead = Lead::with('tags')->find($this->leadId);
            $this->selectedTagIds = $lead ? $lead->tags->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
        } elseif ($this->entityType === 'cliente' && $this->clienteId) {
            $cliente = Cliente::with('crmTags')->find($this->clienteId);
            $this->selectedTagIds = $cliente ? $cliente->crmTags->pluck('id')->map(fn ($id) => (string) $id)->all() : [];
        }
    }

    public function toggleTag(int $tagId): void
    {
        $key = array_search((string) $tagId, $this->selectedTagIds, true);
        if ($key !== false) {
            array_splice($this->selectedTagIds, $key, 1);
        } else {
            $this->selectedTagIds[] = (string) $tagId;
        }
    }

    public function save(): void
    {
        $this->authorize('crm.update');
        try {
            if ($this->entityType === 'lead' && $this->leadId) {
                $lead = Lead::findOrFail($this->leadId);
                $lead->tags()->sync($this->selectedTagIds);
            } elseif ($this->entityType === 'cliente' && $this->clienteId) {
                $cliente = Cliente::findOrFail($this->clienteId);
                $cliente->crmTags()->sync($this->selectedTagIds);
            }
            $this->dispatch('tags-saved');
        } catch (\Exception $e) {
            $this->flashToast('error', $e->getMessage());
        }
    }

    public function getTagsProperty()
    {
        return Tag::orderBy('nombre')->get();
    }

    public function render()
    {
        return view('livewire.crm.tag-picker-live');
    }
}
