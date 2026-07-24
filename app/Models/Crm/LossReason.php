<?php

namespace App\Models\Crm;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LossReason extends Model
{
    use BelongsToSucursal;

    protected $fillable = ['sucursal_id', 'nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class, 'motivo_perdida_id');
    }
}
