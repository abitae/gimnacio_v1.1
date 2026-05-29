<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;

class BioTimeSyncLog extends Model
{
    protected $table = 'bio_time_sync_logs';

    protected $fillable = [
        'batch_id',
        'entity',
        'biotime_id',
        'status',
        'action',
        'payload',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'biotime_id' => 'integer',
            'payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }
}
