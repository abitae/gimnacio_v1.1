<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;

class BioTimeSyncBatch extends Model
{
    protected $table = 'bio_time_sync_batches';

    protected $fillable = [
        'batch_id',
        'sucursal_id',
        'entity',
        'status',
        'received',
        'processed',
        'failed',
        'agent_timestamp',
        'received_at',
        'processed_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'received' => 'integer',
            'processed' => 'integer',
            'failed' => 'integer',
            'agent_timestamp' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }
}
