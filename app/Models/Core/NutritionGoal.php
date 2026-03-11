<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class NutritionGoal extends Model
{
    use SoftDeletes;

    protected $table = 'nutrition_goals';

    protected $fillable = [
        'cliente_id',
        'trainer_user_id',
        'objetivo',
        'objetivo_personalizado',
        'fecha_inicio',
        'fecha_objetivo',
        'observaciones',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_objetivo' => 'date',
        ];
    }

    public const OBJETIVOS = [
        'bajar_grasa' => 'Bajar grasa',
        'ganar_masa' => 'Ganar masa muscular',
        'mejorar_resistencia' => 'Mejorar resistencia',
        'mejorar_salud' => 'Mejorar salud',
        'mantener_peso' => 'Mantener peso',
        'personalizado' => 'Personalizado',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function trainerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'trainer_user_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(NutritionGoalProgress::class, 'nutrition_goal_id');
    }
}
