<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketReprint extends Model
{
    protected $fillable = [
        'venta_id',
        'user_id',
        'reprinted_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'reprinted_at' => 'datetime',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
