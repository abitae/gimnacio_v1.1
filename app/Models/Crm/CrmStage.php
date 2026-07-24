<?php

namespace App\Models\Crm;

use App\Models\Concerns\BelongsToSucursal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CrmStage extends Model
{
    use BelongsToSucursal;
    use HasFactory;

    protected $table = 'crm_stages';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'orden',
        'is_default',
        'is_won',
        'is_lost',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'is_default' => 'boolean',
            'is_won' => 'boolean',
            'is_lost' => 'boolean',
        ];
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class, 'stage_id');
    }
}
