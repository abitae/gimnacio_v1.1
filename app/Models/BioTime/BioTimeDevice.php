<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;

class BioTimeDevice extends Model
{
    public const ACCESS_ROLE_ENTRADA = 'entrada';

    public const ACCESS_ROLE_SALIDA = 'salida';

    public const ACCESS_ROLE_AMBOS = 'ambos';

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
        'access_role',
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

    public static function isValidAccessRole(?string $role): bool
    {
        return $role === null || $role === '' || in_array($role, [
            self::ACCESS_ROLE_ENTRADA,
            self::ACCESS_ROLE_SALIDA,
            self::ACCESS_ROLE_AMBOS,
        ], true);
    }

    public function getIsOnlineAttribute(): bool
    {
        return in_array((int) $this->state, [1, 2], true)
            || ($this->last_activity !== null && $this->last_activity->gt(now()->subMinutes(10)));
    }
}
