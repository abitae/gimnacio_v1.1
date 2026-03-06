<?php

namespace App\Models\Crm;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    protected $table = 'crm_campaigns';

    public const TIPOS = [
        'captacion' => 'Captación',
        'reactivacion' => 'Reactivación',
        'renovacion' => 'Renovación',
    ];

    public const ESTADOS = [
        'draft' => 'Borrador',
        'active' => 'Activa',
        'done' => 'Finalizada',
    ];

    protected $fillable = [
        'nombre',
        'tipo',
        'estado',
        'filtros',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filtros' => 'array',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function targets(): HasMany
    {
        return $this->hasMany(CampaignTarget::class, 'campaign_id');
    }
}
