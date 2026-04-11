<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'razon_social',
        'ruc',
        'direccion',
        'telefono',
        'email',
        'logo',
        'estado',
    ];

    public function sucursales(): HasMany
    {
        return $this->hasMany(Sucursal::class);
    }
}
