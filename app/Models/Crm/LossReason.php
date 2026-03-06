<?php

namespace App\Models\Crm;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LossReason extends Model
{
    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'motivo_perdida_id');
    }
}
