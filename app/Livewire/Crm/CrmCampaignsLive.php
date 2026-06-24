<?php

namespace App\Livewire\Crm;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Crm\Campaign;
use App\Services\Crm\CampaignService;
use Livewire\Component;
use Livewire\WithPagination;

class CrmCampaignsLive extends Component
{
    use FlashesToast, WithPagination;

    public $search = '';

    public $modalForm = false;

    public $editingId = null;

    public $nombre = '';

    public $tipo = 'captacion';

    public $estado = 'draft';

    public $discount_coupon_id = '';

    protected CampaignService $campaignService;

    protected $paginationTheme = 'tailwind';

    public function boot(CampaignService $campaignService)
    {
        $this->campaignService = $campaignService;
    }

    public function mount()
    {
        $this->authorize('crm.ver');
    }

    public function openCreate()
    {
        $this->authorize('crm.crear');
        $this->editingId = null;
        $this->nombre = '';
        $this->tipo = 'captacion';
        $this->estado = 'draft';
        $this->discount_coupon_id = '';
        $this->modalForm = true;
    }

    public function openEdit(int $id)
    {
        $this->authorize('crm.editar');
        $c = Campaign::find($id);
        if (! $c) {
            return;
        }
        $this->editingId = $id;
        $this->nombre = $c->nombre;
        $this->tipo = $c->tipo;
        $this->estado = $c->estado;
        $this->discount_coupon_id = $c->discount_coupon_id ? (string) $c->discount_coupon_id : '';
        $this->modalForm = true;
    }

    public function save()
    {
        $this->authorize($this->editingId ? 'crm.editar' : 'crm.crear');
        $this->validate(['nombre' => 'required|string|max:120']);
        $payload = [
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'estado' => $this->estado,
            'discount_coupon_id' => $this->discount_coupon_id ? (int) $this->discount_coupon_id : null,
        ];
        if ($this->editingId) {
            $c = Campaign::findOrFail($this->editingId);
            $this->campaignService->update($c, $payload);
        } else {
            $this->campaignService->create($payload);
        }
        $this->modalForm = false;
        $this->editingId = null;
        $this->flashToast('success', 'Campaña guardada');
    }

    public function closeModal()
    {
        $this->modalForm = false;
        $this->editingId = null;
    }

    public function render()
    {
        $q = Campaign::query()->with('createdBy')->orderBy('updated_at', 'desc');
        if ($this->search) {
            $q->where('nombre', 'like', '%'.$this->search.'%');
        }
        $campaigns = $q->paginate(15);

        return view('livewire.crm.crm-campaigns-live', [
            'campaigns' => $campaigns,
            'coupons' => \App\Models\Core\DiscountCoupon::query()->where('estado', 'activo')->orderBy('codigo')->get(['id', 'codigo', 'nombre']),
        ]);
    }
}
