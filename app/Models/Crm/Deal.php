<?php

namespace App\Models\Crm;

use App\Models\Core\Cliente;
use App\Models\Core\Membresia;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    use SoftDeletes;

    protected $table = 'crm_deals';

    public const ESTADOS = ['open', 'won', 'lost'];

    protected $fillable = [
        'lead_id',
        'cliente_id',
        'membresia_id',
        'precio_objetivo',
        'descuento_sugerido',
        'probabilidad',
        'fecha_estimada_cierre',
        'estado',
        'motivo_interes',
        'objeciones',
        'motivo_perdida_id',
        'notas',
        'assigned_to',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'precio_objetivo' => 'decimal:2',
            'descuento_sugerido' => 'decimal:2',
            'probabilidad' => 'integer',
            'fecha_estimada_cierre' => 'date',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }

    public function motivoPerdida(): BelongsTo
    {
        return $this->belongsTo(LossReason::class, 'motivo_perdida_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class);
    }

    public function isWon(): bool
    {
        return $this->estado === 'won';
    }

    public function isLost(): bool
    {
        return $this->estado === 'lost';
    }
}
