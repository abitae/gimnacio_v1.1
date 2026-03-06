<?php

namespace App\Models\Crm;

use App\Models\Core\Cliente;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $table = 'crm_leads';

    public const ESTADOS = [
        'nuevo',
        'contactado',
        'interesado',
        'agendo_visita',
        'visito',
        'negociacion',
        'ganado',
        'perdido',
        'no_responde',
        'convertido',
    ];

    protected $fillable = [
        'tipo_documento',
        'numero_documento',
        'nombres',
        'apellidos',
        'telefono',
        'whatsapp',
        'email',
        'direccion',
        'canal_origen',
        'sede',
        'interes_principal',
        'estado',
        'stage_id',
        'assigned_to',
        'cliente_id',
        'fecha_ultimo_contacto',
        'notas',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'fecha_ultimo_contacto' => 'datetime',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmStage::class, 'stage_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'lead_tag')->withTimestamps();
    }

    public function campaignTargets(): HasMany
    {
        return $this->hasMany(CampaignTarget::class, 'lead_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        $n = trim(($this->nombres ?? '') . ' ' . ($this->apellidos ?? ''));
        return $n !== '' ? $n : ($this->telefono ?? 'Sin nombre');
    }

    public function getWhatsAppUrlAttribute(): ?string
    {
        $tel = $this->whatsapp ?: $this->telefono;
        if (!$tel || trim((string) $tel) === '') {
            return null;
        }
        $digits = preg_replace('/\D/', '', $tel);
        $digits = ltrim($digits, '0');
        if (str_starts_with($digits, '51') && strlen($digits) >= 11) {
            return 'https://wa.me/' . $digits;
        }
        return 'https://wa.me/51' . $digits;
    }

    public function isConvertido(): bool
    {
        return $this->estado === 'convertido' || $this->cliente_id !== null;
    }
}
