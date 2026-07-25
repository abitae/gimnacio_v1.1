<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;

class BioTimeArea extends Model
{
    protected $table = 'bio_time_areas';

    protected $fillable = [
        'sucursal_id',
        'biotime_id',
        'area_code',
        'area_name',
        'parent_biotime_id',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'biotime_id' => 'integer',
            'parent_biotime_id' => 'integer',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
