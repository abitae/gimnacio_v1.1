<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentableSpaceRate extends Model
{
    protected $table = 'rentable_space_rates';

    protected $fillable = [
        'rentable_space_id',
        'tipo_tarifa',
        'nombre',
        'precio',
        'hora_inicio',
        'hora_fin',
        'dia_semana',
        'fecha_especial',
        'sede_id',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'hora_inicio' => 'datetime:H:i',
            'hora_fin' => 'datetime:H:i',
            'fecha_especial' => 'date',
        ];
    }

    public function rentableSpace(): BelongsTo
    {
        return $this->belongsTo(RentableSpace::class);
    }
}
