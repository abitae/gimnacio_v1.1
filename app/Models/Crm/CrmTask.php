<?php

namespace App\Models\Crm;

use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTask extends Model
{
    use SoftDeletes;

    protected $table = 'crm_tasks';

    public const TIPOS = [
        'call' => 'Llamar',
        'whatsapp' => 'Escribir WhatsApp',
        'schedule_visit' => 'Agendar visita',
        'send_promo' => 'Enviar promoción',
        'follow_up' => 'Seguimiento',
        'other' => 'Otro',
    ];

    public const PRIORIDADES = [
        'low' => 'Baja',
        'medium' => 'Media',
        'high' => 'Alta',
    ];

    public const ESTADOS = [
        'pending' => 'Pendiente',
        'done' => 'Hecha',
        'overdue' => 'Vencida',
    ];

    protected $fillable = [
        'lead_id',
        'cliente_id',
        'deal_id',
        'tipo',
        'fecha_hora_programada',
        'prioridad',
        'estado',
        'completed_at',
        'assigned_to',
        'created_by',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_programada' => 'datetime',
            'completed_at' => 'datetime',
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

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTipoLabelAttribute(): string
    {
        return self::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function getPrioridadLabelAttribute(): string
    {
        return self::PRIORIDADES[$this->prioridad] ?? $this->prioridad;
    }

    public function getEstadoLabelAttribute(): string
    {
        return self::ESTADOS[$this->estado] ?? $this->estado;
    }
}
