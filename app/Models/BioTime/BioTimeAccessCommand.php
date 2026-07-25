<?php

declare(strict_types=1);

namespace App\Models\BioTime;

use App\Models\Core\Cliente;
use App\Models\System\Sucursal;
use Database\Factories\BioTime\BioTimeAccessCommandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BioTimeAccessCommand extends Model
{
    use HasFactory;

    public const ACTION_ACTIVATE = 'activate';

    public const ACTION_DEACTIVATE = 'deactivate';

    public const ACTION_DELETE = 'delete';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_ACKED = 'acked';

    public const STATUS_FAILED = 'failed';

    protected $table = 'bio_time_access_commands';

    protected $fillable = [
        'sucursal_id',
        'idempotency_key',
        'cliente_id',
        'emp_code',
        'action',
        'desired_area_biotime_id',
        'ensure_create',
        'first_name',
        'last_name',
        'status',
        'attempts',
        'leased_at',
        'lease_expires_at',
        'last_error',
        'acked_at',
    ];

    protected function casts(): array
    {
        return [
            'sucursal_id' => 'integer',
            'cliente_id' => 'integer',
            'desired_area_biotime_id' => 'integer',
            'ensure_create' => 'boolean',
            'attempts' => 'integer',
            'leased_at' => 'datetime',
            'lease_expires_at' => 'datetime',
            'acked_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $command): void {
            $command->idempotency_key ??= (string) Str::uuid();
        });
    }

    protected static function newFactory(): BioTimeAccessCommandFactory
    {
        return BioTimeAccessCommandFactory::new();
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public static function isValidAction(string $action): bool
    {
        return in_array($action, [
            self::ACTION_ACTIVATE,
            self::ACTION_DEACTIVATE,
            self::ACTION_DELETE,
        ], true);
    }
}
