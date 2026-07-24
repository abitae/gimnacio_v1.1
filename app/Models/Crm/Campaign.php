<?php

namespace App\Models\Crm;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\Core\DiscountCoupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use BelongsToSucursal;

    protected $table = 'crm_campaigns';

    public const TIPOS = [
        'captacion' => 'Captación',
        'reactivacion' => 'Reactivación',
        'renovacion' => 'Renovación',
    ];

    public const ESTADOS = [
        'draft' => 'Borrador',
        'active' => 'Activa',
        'done' => 'Finalizada',
    ];

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'tipo',
        'estado',
        'filtros',
        'discount_coupon_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function discountCoupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class, 'discount_coupon_id');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(CampaignTarget::class, 'campaign_id');
    }
}
