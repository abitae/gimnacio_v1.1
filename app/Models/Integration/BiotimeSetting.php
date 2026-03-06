<?php

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class BiotimeSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'base_url',
        'username',
        'password',
        'auth_type',
        'enabled',
        'last_tested_at',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'encrypted',
            'enabled' => 'boolean',
            'last_tested_at' => 'datetime',
        ];
    }

    /**
     * Get the singleton instance (first record). If table is empty, create one from config.
     */
    public static function getInstance(): ?self
    {
        if (! Schema::hasTable('biotime_settings')) {
            return null;
        }

        $instance = static::first();

        if ($instance) {
            return $instance;
        }

        $config = config('services.biotime', []);
        if (empty($config['base_url'])) {
            return null;
        }

        return static::create([
            'base_url' => rtrim($config['base_url'] ?? '', '/'),
            'username' => $config['username'] ?? '',
            'password' => $config['password'] ?? '',
            'auth_type' => $config['auth_type'] ?? 'jwt',
            'enabled' => true,
        ]);
    }

    /**
     * Get credentials for API client (base_url, username, password, auth_type).
     */
    public function getCredentials(): array
    {
        return [
            'base_url' => $this->base_url ? rtrim($this->base_url, '/') : '',
            'username' => $this->username ?? '',
            'password' => $this->password ?? '',
            'auth_type' => in_array($this->auth_type, ['jwt', 'token'], true) ? $this->auth_type : 'jwt',
        ];
    }
}
