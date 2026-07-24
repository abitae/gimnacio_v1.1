<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HealthRecord extends Model
{
    use BelongsToSucursal;

    protected $table = 'health_records';

    protected $fillable = [
        'sucursal_id',
        'cliente_id',
        'enfermedades',
        'alergias',
        'medicacion',
        'restricciones_medicas',
        'lesiones',
        'observaciones',
        'actualizado_por',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por');
    }
}
