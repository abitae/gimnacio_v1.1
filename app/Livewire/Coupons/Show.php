<?php

namespace App\Livewire\Coupons;

use App\Models\Core\DiscountCoupon;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public DiscountCoupon $coupon;

    public int $perPage = 10;

    protected $paginationTheme = 'tailwind';

    public function mount(DiscountCoupon $coupon): void
    {
        $this->authorize('cupones.view');
        $this->coupon = $coupon;
    }

    public function render()
    {
        $usages = $this->coupon->usages()->with(['usadoPor', 'usable'])->orderByDesc('created_at')->paginate($this->perPage);

        return view('livewire.coupons.show', [
            'usages' => $usages,
        ])->layout('layouts.app', ['title' => 'Cupón: ' . $this->coupon->codigo]);
    }
}
