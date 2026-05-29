<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;

class BioTimeDepartment extends Model
{
    protected $table = 'bio_time_departments';

    protected $fillable = [
        'biotime_id',
        'dept_code',
        'dept_name',
        'parent_biotime_id',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'biotime_id' => 'integer',
            'parent_biotime_id' => 'integer',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
