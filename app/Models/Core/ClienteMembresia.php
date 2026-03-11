<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteMembresia extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'membresia_id',
        'fecha_matricula',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'precio_lista',
        'descuento_monto',
        'precio_final',
        'asesor_id',
        'canal_venta',
        'fechas_congelacion',
        'motivo_cancelacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_matricula' => 'date',
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fechas_congelacion' => 'array',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class);
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class);
    }
}
