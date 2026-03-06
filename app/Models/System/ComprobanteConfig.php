<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ComprobanteConfig extends Model
{
    use HasFactory;

    protected $table = 'comprobantes_config';

    protected $fillable = [
        'tipo',
        'serie',
        'numero_actual',
        'numero_inicial',
        'numero_final',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'numero_actual' => 'integer',
            'numero_inicial' => 'integer',
            'numero_final' => 'integer',
        ];
    }

    // Métodos de negocio
    public function obtenerSiguienteNumero(): int
    {
        $siguiente = $this->numero_actual + 1;
        
        if ($this->numero_final && $siguiente > $this->numero_final) {
            throw new \Exception('Se ha alcanzado el límite de numeración para esta serie.');
        }

        return $siguiente;
    }

    public function incrementarNumero(): bool
    {
        $this->numero_actual = $this->obtenerSiguienteNumero();
        return $this->save();
    }
}
