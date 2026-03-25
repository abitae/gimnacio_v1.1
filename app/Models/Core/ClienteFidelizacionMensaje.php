<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClienteFidelizacionMensaje extends Model
{
    public const PRIORIDADES = [
        'baja' => 'Baja',
        'media' => 'Media',
        'alta' => 'Alta',
    ];

    protected $table = 'cliente_fidelizacion_mensajes';

    protected $fillable = [
        'cliente_id',
        'user_id',
        'prioridad',
        'mensaje',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function autor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getPrioridadLabelAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }
}
