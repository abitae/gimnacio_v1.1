<?php

namespace App\Livewire\Coupons;

use App\Livewire\Concerns\FlashesToast;
use App\Models\Core\DiscountCoupon;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use FlashesToast, WithPagination;

    public string $search = '';

    public string $estadoFilter = '';

    public int $perPage = 15;

    protected $paginationTheme = 'tailwind';

    public function mount(): void
    {
        $this->authorize('cupon.ver');
    }

    public function delete(int $couponId): void
    {
        $this->authorize('cupon.eliminar');
        $coupon = DiscountCoupon::findOrFail($couponId);
        if ($coupon->usages()->exists()) {
            $this->flashToast('error', 'No se puede eliminar un cupón con usos registrados.');

            return;
        }
        $coupon->delete();
        $this->flashToast('success', 'Cupón eliminado.');
    }

    public function render()
    {
        $query = DiscountCoupon::query()
            ->when($this->search, fn ($q) => $q->where('codigo', 'like', '%'.$this->search.'%')
                ->orWhere('nombre', 'like', '%'.$this->search.'%'))
            ->when($this->estadoFilter, fn ($q) => $q->where('estado', $this->estadoFilter))
            ->orderByDesc('created_at');

        $coupons = $query->paginate($this->perPage);

        return view('livewire.coupons.index', [
            'coupons' => $coupons,
        ])->layout('layouts.app', ['title' => 'Cupones de descuento']);
    }
}
