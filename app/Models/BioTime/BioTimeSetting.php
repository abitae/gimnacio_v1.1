<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BioTimeSetting extends Model
{
    protected $fillable = [
        'webhook_secret',
        'last_received_at',
    ];

    protected function casts(): array
    {
        return [
            'webhook_secret' => 'encrypted',
            'last_received_at' => 'datetime',
        ];
    }

    public static function current(): self
    {
        $setting = self::query()->first();

        if ($setting instanceof self) {
            return $setting;
        }

        $setting = new self;
        $setting->forceFill(['id' => 1])->save();

        return $setting;
    }

    public static function activeSecret(): ?string
    {
        $secret = self::current()->webhook_secret;

        return filled($secret) ? (string) $secret : env('BIOTIME_WEBHOOK_SECRET');
    }

    public function regenerateSecret(): string
    {
        $secret = 'bt_'.Str::random(64);
        $this->forceFill(['webhook_secret' => $secret])->save();

        return $secret;
    }
}
