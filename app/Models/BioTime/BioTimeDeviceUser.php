<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioTimeDeviceUser extends Model
{
    protected $table = 'bio_time_device_users';

    protected $fillable = [
        'bio_time_device_id',
        'cliente_id',
        'emp_code',
        'managed',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'bio_time_device_id' => 'integer',
            'cliente_id' => 'integer',
            'managed' => 'boolean',
            'synced_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(BioTimeDevice::class, 'bio_time_device_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
