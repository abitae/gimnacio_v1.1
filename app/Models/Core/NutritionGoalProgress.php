<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NutritionGoalProgress extends Model
{
    protected $table = 'nutrition_goal_progress';

    protected $fillable = [
        'nutrition_goal_id',
        'fecha',
        'peso',
        'medidas',
        'observaciones',
        'fotos',
        'adherencia',
        'progreso_general',
        'registrado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'peso' => 'decimal:2',
            'medidas' => 'array',
            'fotos' => 'array',
        ];
    }

    public function nutritionGoal(): BelongsTo
    {
        return $this->belongsTo(NutritionGoal::class);
    }

    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrado_por');
    }
}
