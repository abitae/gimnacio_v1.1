<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaServicio extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'categorias_servicios';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
        ];
    }

    // Relaciones
    public function servicios(): HasMany
    {
        return $this->hasMany(ServicioExterno::class, 'categoria_id');
    }
}
