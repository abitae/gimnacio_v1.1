<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;

class CajaMovimiento extends Model
{
    use HasFactory;

    protected $table = 'caja_movimientos';

    public const CATEGORIA_APERTURA = 'apertura';
    public const CATEGORIA_MANUAL_INGRESO = 'manual_ingreso';
    public const CATEGORIA_MANUAL_SALIDA = 'manual_salida';
    public const CATEGORIA_MEMBRESIA = 'membresia';
    public const CATEGORIA_CLASE = 'clase';
    public const CATEGORIA_CUOTA = 'cuota';
    public const CATEGORIA_POS = 'pos';
    public const CATEGORIA_ALQUILER = 'alquiler';
    public const CATEGORIA_AJUSTE = 'ajuste';

    public const ORIGEN_CAJA = 'caja';
    public const ORIGEN_CLIENTE_MEMBRESIAS = 'cliente_membresias';
    public const ORIGEN_CLIENTE_MATRICULAS = 'cliente_matriculas';
    public const ORIGEN_ENROLLMENT_INSTALLMENTS = 'enrollment_installments';
    public const ORIGEN_VENTAS = 'ventas';
    public const ORIGEN_RENTALS = 'rentals';
    public const ORIGEN_MANUAL = 'manual';

    protected $fillable = [
        'caja_id',
        'tipo',
        'categoria',
        'origen_modulo',
        'monto',
        'concepto',
        'referencia_tipo',
        'referencia_id',
        'usuario_id',
        'observaciones',
        'fecha_movimiento',
    ];

    protected function casts(): array
    {
        return [
            'monto' => 'decimal:2',
            'fecha_movimiento' => 'datetime',
        ];
    }

    // Relaciones
    public function caja(): BelongsTo
    {
        return $this->belongsTo(Caja::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function referencia(): MorphTo
    {
        return $this->morphTo('referencia', 'referencia_tipo', 'referencia_id');
    }

    // Scopes
    public function scopeEntradas(Builder $query): Builder
    {
        return $query->where('tipo', 'entrada');
    }

    public function scopeSalidas(Builder $query): Builder
    {
        return $query->where('tipo', 'salida');
    }

    public function scopePorCaja(Builder $query, int $cajaId): Builder
    {
        return $query->where('caja_id', $cajaId);
    }

    public function scopePorTipo(Builder $query, string $tipo): Builder
    {
        return $query->where('tipo', $tipo);
    }

    // Métodos de negocio
    public function esEntrada(): bool
    {
        return $this->tipo === 'entrada';
    }

    public function esSalida(): bool
    {
        return $this->tipo === 'salida';
    }

    public function getTipoVisualAttribute(): string
    {
        return match ($this->categoria) {
            self::CATEGORIA_MEMBRESIA => 'Membresia',
            self::CATEGORIA_CLASE => 'Clase',
            self::CATEGORIA_CUOTA => 'Cuota',
            self::CATEGORIA_POS => 'POS',
            self::CATEGORIA_ALQUILER => 'Alquiler',
            self::CATEGORIA_MANUAL_INGRESO => 'Ingreso manual',
            self::CATEGORIA_MANUAL_SALIDA => 'Salida manual',
            self::CATEGORIA_APERTURA => 'Apertura',
            default => 'Ajuste',
        };
    }
}
