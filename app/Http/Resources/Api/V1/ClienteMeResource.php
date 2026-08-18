<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Core\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Cliente
 */
class ClienteMeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombres' => $this->nombres,
            'apellidos' => $this->apellidos,
            'nombre_completo' => trim($this->nombres.' '.$this->apellidos),
            'tipo_documento' => $this->tipo_documento,
            'numero_documento' => $this->numero_documento,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'foto_url' => $this->foto ? asset('storage/'.$this->foto) : null,
            'estado_cliente' => $this->estado_cliente,
            'sucursal' => $this->whenLoaded('sucursal', fn () => $this->sucursal ? [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
            ] : null),
        ];
    }
}
