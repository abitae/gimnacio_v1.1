<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rental extends Model
{
    use SoftDeletes;

    protected $table = 'rentals';

    protected $fillable = [
        'rentable_space_id',
        'cliente_id',
        'nombre_externo',
        'documento_externo',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'precio',
        'estado',
        'descuento',
        'discount_coupon_id',
        'observaciones',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'hora_inicio' => 'datetime:H:i',
            'hora_fin' => 'datetime:H:i',
            'precio' => 'decimal:2',
            'descuento' => 'decimal:2',
        ];
    }

    public const ESTADOS = [
        'reservado' => 'Reservado',
        'confirmado' => 'Confirmado',
        'pagado' => 'Pagado',
        'cancelado' => 'Cancelado',
        'finalizado' => 'Finalizado',
    ];

    public function rentableSpace(): BelongsTo
    {
        return $this->belongsTo(RentableSpace::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function discountCoupon(): BelongsTo
    {
        return $this->belongsTo(DiscountCoupon::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(RentalPayment::class);
    }
}
