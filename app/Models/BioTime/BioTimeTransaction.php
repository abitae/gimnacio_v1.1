<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\Core\Asistencia;
use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioTimeTransaction extends Model
{
    protected $table = 'bio_time_transactions';

    protected $fillable = [
        'sucursal_id',
        'biotime_id',
        'cliente_id',
        'asistencia_id',
        'emp_code',
        'punch_time',
        'punch_state',
        'punch_state_display',
        'verify_type',
        'terminal_sn',
        'terminal_alias',
        'upload_time',
        'department_name',
        'position_name',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'biotime_id' => 'integer',
            'cliente_id' => 'integer',
            'asistencia_id' => 'integer',
            'punch_time' => 'datetime',
            'verify_type' => 'integer',
            'upload_time' => 'datetime',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }
}
