<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\System\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioTimeMapping extends Model
{
    protected $table = 'bio_time_mappings';

    protected $fillable = [
        'mapping_type',
        'biotime_id',
        'target_type',
        'target_id',
        'sucursal_id',
    ];

    protected function casts(): array
    {
        return [
            'biotime_id' => 'integer',
            'target_id' => 'integer',
            'sucursal_id' => 'integer',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }
}
