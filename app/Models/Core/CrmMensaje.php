<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmMensaje extends Model
{
    use HasFactory;

    protected $table = 'crm_mensajes';

    protected $fillable = [
        'cliente_id',
        'canal',
        'destino',
        'contenido',
        'estado',
        'enviado_at',
        'error_mensaje',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'enviado_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function creadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
