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

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }
}
