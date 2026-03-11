<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClienteMatricula extends Model
{
    use HasFactory;

    protected $table = 'cliente_matriculas';

    protected $fillable = [
        'cliente_id',
        'tipo',
        'membresia_id',
        'clase_id',
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
        'sesiones_totales',
        'sesiones_usadas',
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

    public function clase(): BelongsTo
    {
        return $this->belongsTo(Clase::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asesor_id');
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(Pago::class, 'cliente_matricula_id');
    }

    public function asistencias(): HasMany
    {
        return $this->hasMany(Asistencia::class, 'cliente_matricula_id');
    }

    public function installmentPlan(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(EnrollmentInstallmentPlan::class, 'cliente_matricula_id');
    }

    // Métodos de ayuda
    public function esMembresia(): bool
    {
        return $this->tipo === 'membresia';
    }

    public function esClase(): bool
    {
        return $this->tipo === 'clase';
    }

    public function getNombreAttribute(): string
    {
        if ($this->esMembresia() && $this->membresia) {
            return $this->membresia->nombre;
        }
        if ($this->esClase() && $this->clase) {
            return $this->clase->nombre;
        }
        return 'N/A';
    }
}
