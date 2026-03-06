<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GymSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre_gimnasio',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'logo',
        'horarios_acceso',
        'politicas_acceso',
    ];

    protected function casts(): array
    {
        return [
            'horarios_acceso' => 'array',
        ];
    }
}
