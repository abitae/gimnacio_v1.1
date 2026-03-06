<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoutineTemplateDay extends Model
{
    protected $fillable = [
        'routine_template_id',
        'nombre',
        'orden',
    ];

    public function routineTemplate(): BelongsTo
    {
        return $this->belongsTo(RoutineTemplate::class, 'routine_template_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(RoutineTemplateDayExercise::class, 'routine_template_day_id')->orderBy('orden');
    }
}
