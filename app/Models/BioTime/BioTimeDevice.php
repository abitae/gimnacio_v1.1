<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;

class BioTimeDevice extends Model
{
    protected $table = 'bio_time_devices';

    protected $fillable = [
        'biotime_id',
        'serial_number',
        'alias',
        'ip_address',
        'state',
        'area_biotime_id',
        'last_activity',
        'is_attendance',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'biotime_id' => 'integer',
            'state' => 'integer',
            'area_biotime_id' => 'integer',
            'last_activity' => 'datetime',
            'is_attendance' => 'boolean',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function getIsOnlineAttribute(): bool
    {
        return in_array((int) $this->state, [1, 2], true)
            || ($this->last_activity !== null && $this->last_activity->gt(now()->subMinutes(10)));
    }
}
