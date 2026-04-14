<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    protected $fillable = [
        'import_id',
        'fila_numero',
        'estado',
        'data_json',
        'errores_json',
        'modelo_tipo',
        'modelo_id',
    ];

    protected function casts(): array
    {
        return [
            'data_json' => 'array',
            'errores_json' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class);
    }
}
