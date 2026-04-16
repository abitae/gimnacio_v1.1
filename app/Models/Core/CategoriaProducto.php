<?php

namespace App\Models\Core;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaProducto extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'categorias_productos';

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
    public function productos(): HasMany
    {
        return $this->hasMany(Producto::class, 'categoria_id');
    }
}
