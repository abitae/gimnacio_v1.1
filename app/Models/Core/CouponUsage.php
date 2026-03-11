<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CouponUsage extends Model
{
    protected $fillable = [
        'discount_coupon_id',
        'usable_type',
        'usable_id',
        'monto_descuento_aplicado',
        'usado_por',
    ];

    protected function casts(): array
    {
        return [
            'monto_descuento_aplicado' => 'decimal:2',
        ];
    }

    public function discountCoupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class);
    }

    public function usable(): MorphTo
    {
        return $this->morphTo();
    }

    public function usadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usado_por');
    }
}
