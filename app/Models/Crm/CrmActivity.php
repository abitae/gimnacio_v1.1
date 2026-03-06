<?php

namespace App\Models\Crm;

use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmActivity extends Model
{
    use SoftDeletes;

    protected $table = 'crm_activities';

    public const TIPOS = [
        'call' => 'Llamada',
        'whatsapp' => 'WhatsApp',
        'visit' => 'Visita',
        'trial' => 'Prueba',
        'email' => 'Email',
        'note' => 'Nota',
        'other' => 'Otro',
    ];

    protected $fillable = [
        'lead_id',
        'cliente_id',
        'deal_id',
        'tipo',
        'fecha_hora',
        'resultado',
        'observaciones',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }
}
