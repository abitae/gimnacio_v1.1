<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientePlanTraspaso extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'origen_tipo',
        'origen_id',
        'plan_anterior_tipo',
        'plan_anterior_id',
        'plan_nuevo_tipo',
        'plan_nuevo_id',
        'motivo',
        'registrado_por',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
