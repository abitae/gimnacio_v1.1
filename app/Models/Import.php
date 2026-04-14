<?php

namespace App\Models;

use App\Models\System\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Import extends Model
{
    protected $fillable = [
        'tipo_importacion',
        'archivo_nombre',
        'archivo_path',
        'sucursal_id',
        'total_filas',
        'filas_validas',
        'filas_error',
        'filas_importadas',
        'estado',
        'observaciones',
        'opciones',
        'imported_by',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'opciones' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }
}
