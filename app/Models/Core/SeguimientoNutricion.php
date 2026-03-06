<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeguimientoNutricion extends Model
{
    use HasFactory;

    protected $table = 'seguimientos_nutricion';

    protected $fillable = [
        'cliente_id',
        'nutricionista_id',
        'cita_id',
        'tipo',
        'fecha',
        'objetivo',
        'calorias_objetivo',
        'macros',
        'contenido',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'macros' => 'array',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function nutricionista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nutricionista_id');
    }

    public function cita(): BelongsTo
    {
        return $this->belongsTo(Cita::class);
    }
}
