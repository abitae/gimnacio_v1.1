<?php

namespace App\Models\Integration;

use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BiotimeAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'biotime_user_id',
        'cliente_id',
        'device_id',
        'event_time',
        'event_type',
        'result',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'event_time' => 'datetime',
            'raw_payload' => 'array',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
