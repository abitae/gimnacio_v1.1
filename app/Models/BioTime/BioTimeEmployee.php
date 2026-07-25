<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\Core\Cliente;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BioTimeEmployee extends Model
{
    protected $table = 'bio_time_employees';

    protected $fillable = [
        'sucursal_id',
        'biotime_id',
        'emp_code',
        'cliente_id',
        'first_name',
        'last_name',
        'department_biotime_id',
        'department_name',
        'app_status',
        'mobile',
        'email',
        'hire_date',
        'card_no',
        'area_biotime_ids',
        'raw_payload',
        'synced_at',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'biotime_id' => 'integer',
            'cliente_id' => 'integer',
            'department_biotime_id' => 'integer',
            'app_status' => 'integer',
            'hire_date' => 'date',
            'area_biotime_ids' => 'array',
            'raw_payload' => 'array',
            'synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $employee): void {
            if ($employee->sucursal_id || ! $employee->cliente_id) {
                return;
            }

            $employee->sucursal_id = Cliente::query()
                ->withoutGlobalScope('active_sucursal')
                ->whereKey($employee->cliente_id)
                ->value('sucursal_id');
        });
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
