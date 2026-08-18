<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Core\ClienteMatricula;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ClienteMatricula
 */
class MembresiaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fechaFin = $this->fecha_fin;
        $diasRestantes = null;

        if ($fechaFin !== null) {
            $diasRestantes = (int) now()->startOfDay()->diffInDays($fechaFin->copy()->startOfDay(), false);
        }

        return [
            'id' => $this->id,
            'tipo' => $this->tipo,
            'nombre' => $this->nombre,
            'estado' => $this->estado,
            'fecha_inicio' => $this->fecha_inicio?->toDateString(),
            'fecha_fin' => $this->fecha_fin?->toDateString(),
            'dias_restantes' => $diasRestantes,
            'tipo_acceso' => $this->membresia?->tipo_acceso,
            'modalidad_pago' => $this->modalidad_pago,
            'sesiones_totales' => $this->sesiones_totales,
            'sesiones_usadas' => $this->sesiones_usadas,
            'fechas_congelacion' => $this->fechas_congelacion,
            'sucursal' => $this->sucursal?->nombre,
        ];
    }
}
