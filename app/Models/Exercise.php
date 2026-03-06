<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Exercise extends Model
{
    use SoftDeletes;

    public const TIPOS = [
        'fuerza' => 'Fuerza',
        'hipertrofia' => 'Hipertrofia',
        'cardio' => 'Cardio',
        'movilidad' => 'Movilidad',
        'estiramiento' => 'Estiramiento',
    ];

    public const ESTADOS = [
        'activo' => 'Activo',
        'inactivo' => 'Inactivo',
    ];

    protected $fillable = [
        'nombre',
        'grupo_muscular_principal',
        'musculos_secundarios',
        'tipo',
        'nivel',
        'equipamiento',
        'descripcion_tecnica',
        'errores_comunes',
        'consejos_seguridad',
        'video_url',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'musculos_secundarios' => 'array',
        ];
    }

    public function variations(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'exercise_relations',
            'exercise_id',
            'related_exercise_id'
        )->withPivot('relation_type')->wherePivot('relation_type', 'variation')->withTimestamps();
    }

    public function substitutions(): BelongsToMany
    {
        return $this->belongsToMany(
            self::class,
            'exercise_relations',
            'exercise_id',
            'related_exercise_id'
        )->withPivot('relation_type')->wherePivot('relation_type', 'substitution')->withTimestamps();
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
