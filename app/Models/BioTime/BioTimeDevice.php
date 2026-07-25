<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\System\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BioTimeDevice extends Model
{
    public const ACCESS_ROLE_ENTRADA = 'entrada';

    public const ACCESS_ROLE_SALIDA = 'salida';

    public const ACCESS_ROLE_AMBOS = 'ambos';

    protected $table = 'bio_time_devices';

    protected $fillable = [
        'sucursal_id',
        'biotime_id',
        'serial_number',
        'alias',
        'ip_address',
        'state',
        'area_biotime_id',
        'last_activity',
        'is_attendance',
        'access_role',
        'access_enabled',
        'capacity_limit',
        'reported_users_count',
        'protected_users_count',
        'inventory_verified',
        'inventory_source',
        'inventory_synced_at',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'biotime_id' => 'integer',
            'state' => 'integer',
            'area_biotime_id' => 'integer',
            'last_activity' => 'datetime',
            'is_attendance' => 'boolean',
            'access_enabled' => 'boolean',
            'capacity_limit' => 'integer',
            'reported_users_count' => 'integer',
            'protected_users_count' => 'integer',
            'inventory_verified' => 'boolean',
            'inventory_synced_at' => 'datetime',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(BioTimeDeviceUser::class, 'bio_time_device_id');
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
