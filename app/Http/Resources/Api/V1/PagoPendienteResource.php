<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class PagoPendienteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $item = is_array($this->resource) ? $this->resource : [];
        $fecha = $item['fecha_vencimiento'] ?? null;

        if ($fecha !== null) {
            $fecha = Carbon::parse($fecha)->toDateString();
        }

        return [
            'tipo' => $item['tipo'] ?? null,
            'id' => $item['id'] ?? null,
            'nombre' => $item['nombre'] ?? null,
            'saldo_pendiente' => isset($item['saldo_pendiente']) ? round((float) $item['saldo_pendiente'], 2) : 0,
            'estado' => $item['estado'] ?? null,
            'es_vencida' => (bool) ($item['es_vencida'] ?? false),
            'fecha_vencimiento' => $fecha,
            'detalle' => $item['detalle'] ?? null,
        ];
    }
}
