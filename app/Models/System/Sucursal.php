<?php

namespace App\Models\System;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'empresa_id',
        'codigo',
        'nombre',
        'direccion',
        'telefono',
        'email',
        'logo',
        'estado',
        'es_principal',
        'horarios_acceso',
        'politicas_acceso',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'horarios_acceso' => 'array',
        ];
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'sucursal_user')->withTimestamps();
    }

    public function clientes(): HasMany
    {
        return $this->hasMany(\App\Models\Core\Cliente::class);
    }
}
