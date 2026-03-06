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

    protected $fillable = [
        'caja_id',
        'tipo',
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
}
