<?php

namespace App\Models\Core;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Asistencia extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'cliente_membresia_id',
        'cliente_matricula_id',
        'fecha_hora_ingreso',
        'fecha_hora_salida',
        'origen',
        'valido_por_membresia',
        'registrada_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_hora_ingreso' => 'datetime',
            'fecha_hora_salida' => 'datetime',
            'valido_por_membresia' => 'boolean',
        ];
    }

    // Relaciones
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function clienteMembresia(): BelongsTo
    {
        return $this->belongsTo(ClienteMembresia::class);
    }

    public function clienteMatricula(): BelongsTo
    {
        return $this->belongsTo(ClienteMatricula::class);
    }

    public function registradaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registrada_por');
    }
}
