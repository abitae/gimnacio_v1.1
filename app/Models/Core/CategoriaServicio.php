<?php

namespace App\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaServicio extends Model
{
    use HasFactory;

    protected $table = 'categorias_servicios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    // Relaciones
    public function servicios(): HasMany
    {
        return $this->hasMany(ServicioExterno::class, 'categoria_id');
    }
}
