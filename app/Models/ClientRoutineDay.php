<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClientRoutineDay extends Model
{
    protected $fillable = [
        'client_routine_id',
        'nombre',
        'orden',
    ];

    public function clientRoutine(): BelongsTo
    {
        return $this->belongsTo(ClientRoutine::class, 'client_routine_id');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(ClientRoutineDayExercise::class, 'client_routine_day_id')->orderBy('orden');
    }
}
