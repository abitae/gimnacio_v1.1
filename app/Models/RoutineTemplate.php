<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RoutineTemplate extends Model
{
    use SoftDeletes;

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'activa' => 'Activa',
    ];

    protected $fillable = [
        'nombre',
        'objetivo',
        'nivel',
        'duracion_semanas',
        'frecuencia_dias_semana',
        'descripcion',
        'tags',
        'estado',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function days(): HasMany
    {
        return $this->hasMany(RoutineTemplateDay::class, 'routine_template_id')->orderBy('orden');
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
