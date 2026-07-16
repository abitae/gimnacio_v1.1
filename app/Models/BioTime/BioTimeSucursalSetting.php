<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\System\Sucursal;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BioTimeSucursalSetting extends Model
{
    protected $table = 'bio_time_sucursal_settings';

    protected $fillable = [
        'sucursal_id',
        'webhook_secret',
        'area_biotime_id',
        'biotime_base_url',
        'poll_interval_seconds',
        'enabled',
        'last_received_at',
        'last_heartbeat_at',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'webhook_secret' => 'encrypted',
            'area_biotime_id' => 'integer',
            'poll_interval_seconds' => 'integer',
            'enabled' => 'boolean',
            'last_received_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public static function forSucursal(int $sucursalId): self
    {
        /** @var self $setting */
        $setting = self::query()->firstOrCreate(
            ['sucursal_id' => $sucursalId],
            [
                'poll_interval_seconds' => 3600,
                'enabled' => true,
            ]
        );

        return $setting;
    }

    /**
     * Busca por secret en claro. El cast encrypted no permite WHERE directo.
     */
    public static function findBySecret(string $secret): ?self
    {
        if ($secret === '') {
            return null;
        }

        foreach (self::query()->cursor() as $setting) {
            $stored = $setting->webhook_secret;
            if (filled($stored) && hash_equals((string) $stored, $secret)) {
                return $setting;
            }
        }

        return null;
    }

    public function regenerateSecret(): string
    {
        $secret = 'bt_'.Str::random(64);
        $this->forceFill(['webhook_secret' => $secret])->save();

        return $secret;
    }
}
